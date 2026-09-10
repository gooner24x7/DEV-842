<?php

namespace BoqAllocator\Services;

use BoqAllocator\DTOs\AllocationResult;

class BoqAllocationEngine
{
    protected BoqParserService $parser;
    protected AitoolV3Classifier $classifier;
    protected array $config;

    public function __construct(?BoqParserService $parser = null, array $config = [], ?AitoolV3Classifier $classifier = null)
    {
        $this->parser = $parser ?: new BoqParserService();
        $this->classifier = $classifier ?: new AitoolV3Classifier();
        $this->config = $config ?: (function_exists('config') ? config('boq-allocator', []) : []);
    }

    /** Run AITOOLV3 row allocation and return the existing bill-level JSON shape. */
    public function allocate(string $boqPath, string $templatePath, ?string $modelKey = null, ?string $customPromptRules = null, ?callable $progressCallback = null): AllocationResult
    {
        $started = microtime(true);
        $emit = function (string $message, int $percent) use ($progressCallback): void {
            if ($progressCallback) $progressCallback($message, $percent);
        };
        // AITOOLV3 uses fixed prompts; customPromptRules remains only for signature compatibility.
        $pipeline = new AitoolV3Pipeline($this->parser, $this->classifier, $this->config);
        $run = $pipeline->run($boqPath, $templatePath, $modelKey, $progressCallback);
        $template = $run['template'];
        $dictionary = $run['dictionary'];
        $codeTargets = $run['codeTargets'];
        $boq = $run['boq'];
        $records = $run['records'];

        $emit('Phase 5: Building the row-level allocation hierarchy...', 80);
        [$tree, $statistics] = $this->buildTree($template, $records, $codeTargets, $dictionary);
        $totalBills = count($boq['bills']);
        $mappedBills = count($statistics['mapped_bills']);
        $totalRecords = count($records);
        $allocatedRecords = $statistics['allocated_records'];
        $avgConfidence = $allocatedRecords
            ? round($statistics['confidence_total'] / $allocatedRecords * 100, 1)
            : 0;
        $mappingRate = $totalRecords ? $allocatedRecords / $totalRecords : 0;
        $accuracy = round(min(100, max(0, $avgConfidence * ($mappingRate * .2 + .8))), 1);
        $tokens = $run['token_usage'];
        $cost = $run['estimated_cost'];
        $elapsed = round(microtime(true) - $started, 2);
        [$topLevelLabel, $allocationNodeLabel] = $this->hierarchyLabels($template);
        $metadata = [
            'total_bills'=>$totalBills,'mapped_bills'=>$mappedBills,'unmapped_bills'=>$totalBills-$mappedBills,
            'total_records'=>$totalRecords,'allocated_records'=>$allocatedRecords,'unallocated_records'=>$totalRecords-$allocatedRecords,
            'packages_used'=>count($tree),'work_items_used'=>$statistics['allocation_nodes'],
            'top_level_label'=>$topLevelLabel,'allocation_node_label'=>$allocationNodeLabel,
            'engine'=>$run['model_label'],'template'=>basename($templatePath),'execution_time'=>$elapsed.'s',
            'overall_accuracy_score'=>$accuracy.'%','avg_package_confidence'=>$avgConfidence.'%','avg_trade_confidence'=>$avgConfidence.'%',
            'token_usage'=>['input'=>$tokens['input'],'output'=>$tokens['output'],'total'=>$tokens['input']+$tokens['output']],
            'estimated_cost'=>'$'.number_format($cost,5),'cache_hits'=>$run['cache_hits'],'api_requested'=>$run['api_requested'],
        ];
        $emit("Completed BoQ Allocation in {$elapsed}s.", 100);
        return new AllocationResult($metadata, $tree);
    }

    private function buildTree(array $template, array $records, array $targets, array $dictionary): array
    {
        $recordsByCode = [];
        $mappedBills = [];
        $confidenceTotal = 0.0;

        foreach ($records as $record) {
            $decision = (array) ($record['decision'] ?? []);
            $code = (string) ($decision['code'] ?? '');

            // Keep this contract identical to generate-review-tree.php: only
            // final, validated AUTO rows appear in the allocation hierarchy.
            if (($decision['status'] ?? '') !== 'AUTO' || $code === '' || !isset($targets[$code], $dictionary[$code])) {
                continue;
            }

            $recordsByCode[$code][] = $record;
            $mappedBills[(int) ($record['bill'] ?? 0)] = true;
            $confidenceTotal += (float) ($decision['confidence'] ?? 0);
        }

        $tree = [];
        $allocationNodes = 0;

        if ($template['is_tiered']) {
            foreach ($template['list'] as $package) {
                $children = [];

                foreach ($template['tier2_map'][$package['id']] ?? [] as $item) {
                    $code = (string) ($item['code'] ?? '');
                    $allocated = $recordsByCode[$code] ?? [];

                    if ($allocated === []) {
                        continue;
                    }

                    $children[] = $this->allocationNode($item, $code, $allocated, $dictionary[$code]);
                    $allocationNodes++;
                }

                if ($children !== []) {
                    $tree[] = [
                        'id' => $package['id'],
                        'name' => $package['name'],
                        'attributes' => ['package_type' => 'wd_template'],
                        'children' => $children,
                    ];
                }
            }
        } else {
            foreach ($template['list'] as $package) {
                $code = (string) ($package['code'] ?? '');
                $allocated = $recordsByCode[$code] ?? [];

                if ($allocated === []) {
                    continue;
                }

                $tree[] = $this->allocationNode($package, $code, $allocated, $dictionary[$code], 'wd_template');
                $allocationNodes++;
            }
        }

        return [$tree, [
            'allocated_records' => array_sum(array_map('count', $recordsByCode)),
            'allocation_nodes' => $allocationNodes,
            'mapped_bills' => array_keys($mappedBills),
            'confidence_total' => $confidenceTotal,
        ]];
    }

    private function allocationNode(
        array $item,
        string $code,
        array $records,
        array $dictionaryItem,
        string $packageType = 'tier2_item'
    ): array
    {
        $confidence = array_sum(array_map(
            static fn (array $record): float => (float) ($record['decision']['confidence'] ?? 0),
            $records
        ));

        return [
            'id' => $item['id'],
            'name' => $item['name'],
            'attributes' => [
                'package_type' => $packageType,
                'allocation_code' => $code,
                'allocated_lines' => count($records),
                'average_confidence' => round($confidence / count($records) * 100, 1),
                'ai_rationale' => 'AITOOLV3 row-level allocation',
            ],
            'source_evidence' => array_map(fn (array $record): string => $this->sourceEvidence($record), $records),
            'children' => [],
        ];
    }

    private function sourceEvidence(array $record): string
    {
        $quantity = (string) ($record['quantity'] ?? '');
        $unit = trim((string) ($record['unit'] ?? ''));
        $quantityText = trim($quantity . ' ' . $unit);

        return implode("\n", array_filter([
            sprintf(
                'Item %s | Bill %d: %s | Section %s',
                (string) ($record['item_id'] ?? ''),
                (int) ($record['bill'] ?? 0),
                (string) ($record['bill_name'] ?? ''),
                (string) ($record['section'] ?? '')
            ),
            (string) ($record['description'] ?? ''),
            $quantityText !== '' ? 'Quantity: ' . $quantityText : '',
        ]));
    }

    private function hierarchyLabels(array $template): array
    {
        if (($template['profile'] ?? '') === 'nrm2-v1') {
            return ['work sections', 'work items'];
        }

        if ($template['is_tiered'] ?? false) {
            return ['group elements', 'cost elements'];
        }

        return ['work packages', 'work packages'];
    }
}
