<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Service\ProjectService;
use BoqAllocator\Services\BoqAllocationEngine;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ZipArchive;

class ProjectServiceBoqTest extends TestCase
{
    public function testSupportedTemplateKeysMapToPackagedFiles(): void
    {
        $templates = [
            'nrm1' => 'NRM1 template.csv',
            'nrm2' => 'NRM2 template.csv',
            'wd' => 'WD template.csv',
        ];

        self::assertSame($templates, ProjectService::getBoqTemplates());

        $templateDirectory = dirname(__DIR__, 2) . '/packages/laravel-boq-allocator/templates';
        foreach ($templates as $filename) {
            self::assertFileIsReadable($templateDirectory . '/' . $filename);
        }
    }

    public function testPreviewKeysAreUniqueWhenAllocatorIdsRepeat(): void
    {
        $service = (new ReflectionClass(ProjectService::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(ProjectService::class, 'addSelectionKeys');
        $method->setAccessible(true);

        $tree = $method->invoke($service, [
            [
                'id' => 'parent_1',
                'name' => 'First',
                'children' => [['id' => 't2_unmapped', 'name' => 'General']],
            ],
            [
                'id' => 'parent_2',
                'name' => 'Second',
                'children' => [['id' => 't2_unmapped', 'name' => 'General']],
            ],
        ]);

        self::assertNotSame(
            $tree[0]['children'][0]['selection_key'],
            $tree[1]['children'][0]['selection_key']
        );
    }

    public function testBundledAllocatorConvertsARealXlsxForEachTemplate(): void
    {
        $workbookPath = $this->createBoqWorkbook();
        $templateDirectory = dirname(__DIR__, 2) . '/packages/laravel-boq-allocator/templates';

        try {
            foreach (ProjectService::getBoqTemplates() as $filename) {
                $engine = new BoqAllocationEngine(null, [
                    'default_model' => 'gpt-5.6-luna',
                    'api_keys' => ['openai' => ''],
                    'decision_cache_path' => sys_get_temp_dir() . '/boq-test-decision-cache.php',
                ]);

                $result = $engine->allocate($workbookPath, $templateDirectory . '/' . $filename);

                self::assertSame(1, $result->metadata['total_bills'], $filename);
                self::assertSame(1, $result->metadata['mapped_bills'], $filename);
                self::assertSame(1, $result->metadata['allocated_records'], $filename);
                self::assertStringContainsString('Excavate foundation trenches', json_encode($result->workPackages), $filename);
            }
        } finally {
            @unlink($workbookPath);
        }
    }

    public function testNrm2TreePreservesEachAllocatedWorkItemInASection(): void
    {
        $engine = (new ReflectionClass(BoqAllocationEngine::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(BoqAllocationEngine::class, 'buildTree');
        $method->setAccessible(true);
        $items = [];
        $targets = [];
        $dictionary = [];
        $records = [];

        foreach (range(1, 5) as $index) {
            $code = '3.' . $index;
            $id = 'item_' . $index;
            $items[] = ['id' => $id, 'code' => $code, 'name' => $index . ' Work item'];
            $targets[$code] = ['target' => 'section_3', 'target_tier2' => $id];
            $dictionary[$code] = ['title' => $index . ' Work item'];
            $records[] = [
                'item_id' => (string) $index,
                'bill' => 1,
                'bill_name' => 'Demolition',
                'section' => '1',
                'description' => 'Allocated line ' . $index,
                'quantity' => 1,
                'unit' => 'item',
                'decision' => ['status' => 'AUTO', 'code' => $code, 'confidence' => .95],
            ];
        }

        [$tree, $statistics] = $method->invoke($engine, [
            'profile' => 'nrm2-v1',
            'is_tiered' => true,
            'list' => [['id' => 'section_3', 'name' => 'Section 3: Demolitions']],
            'tier2_map' => ['section_3' => $items],
        ], $records, $targets, $dictionary);

        self::assertCount(1, $tree);
        self::assertCount(5, $tree[0]['children']);
        self::assertSame(5, $statistics['allocated_records']);
        self::assertSame(5, $statistics['allocation_nodes']);
    }

    public function testSelectingALeafKeepsItsHierarchyAndDiscardsOtherBranches(): void
    {
        $service = (new ReflectionClass(ProjectService::class))->newInstanceWithoutConstructor();
        $addKeys = new \ReflectionMethod(ProjectService::class, 'addSelectionKeys');
        $addKeys->setAccessible(true);
        $filter = new \ReflectionMethod(ProjectService::class, 'filterPreviewTree');
        $filter->setAccessible(true);

        $tree = $addKeys->invoke($service, [[
            'id' => 'parent',
            'name' => 'Parent',
            'children' => [
                ['id' => 'selected', 'name' => 'Selected'],
                ['id' => 'discarded', 'name' => 'Discarded'],
            ],
        ]]);

        $selectedKey = $tree[0]['children'][0]['selection_key'];
        $filtered = $filter->invoke($service, $tree, [$selectedKey => true]);

        self::assertSame('Parent', $filtered[0]['name']);
        self::assertSame('Selected', $filtered[0]['children'][0]['name']);
        self::assertCount(1, $filtered[0]['children']);
        self::assertArrayNotHasKey('selection_key', $filtered[0]);
        self::assertArrayNotHasKey('selection_key', $filtered[0]['children'][0]);
    }

    private function createBoqWorkbook(): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'boq-test-');
        self::assertNotFalse($temporaryPath);
        @unlink($temporaryPath);
        $workbookPath = $temporaryPath . '.xlsx';

        $zip = new ZipArchive();
        self::assertTrue($zip->open($workbookPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML);
        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML);
        $zip->addFromString('xl/workbook.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="General Summary" sheetId="1" r:id="rId1"/>
    <sheet name="Bill Items" sheetId="2" r:id="rId2"/>
  </sheets>
</workbook>
XML);
        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
</Relationships>
XML);
        $zip->addFromString('xl/worksheets/sheet1.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>Bill 1</t></is></c><c r="B1" t="inlineStr"><is><t>Groundworks</t></is></c></row></sheetData>
</worksheet>
XML);
        $zip->addFromString('xl/worksheets/sheet2.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1"><c r="A1" t="inlineStr"><is><t>Bill</t></is></c><c r="B1" t="inlineStr"><is><t>Section</t></is></c><c r="C1" t="inlineStr"><is><t>Page</t></is></c><c r="D1" t="inlineStr"><is><t>Ref</t></is></c><c r="E1" t="inlineStr"><is><t>Description</t></is></c><c r="F1" t="inlineStr"><is><t>Quantity</t></is></c><c r="G1" t="inlineStr"><is><t>Unit</t></is></c><c r="H1" t="inlineStr"><is><t>Rate</t></is></c><c r="I1" t="inlineStr"><is><t>Extension</t></is></c><c r="J1" t="inlineStr"><is><t>Activity</t></is></c></row>
    <row r="2"><c r="A2" t="inlineStr"><is><t>1</t></is></c><c r="B2" t="inlineStr"><is><t>Substructure</t></is></c><c r="C2" t="inlineStr"><is><t>1</t></is></c><c r="D2" t="inlineStr"><is><t>A1</t></is></c><c r="E2" t="inlineStr"><is><t>Excavate foundation trenches</t></is></c><c r="F2" t="inlineStr"><is><t>10</t></is></c><c r="G2" t="inlineStr"><is><t>m3</t></is></c></row>
  </sheetData>
</worksheet>
XML);
        self::assertTrue($zip->close());

        return $workbookPath;
    }
}
