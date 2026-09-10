<?php

declare(strict_types=1);

namespace App\Service;

use App\DataProvider\AnswerDataProvider;
use App\DataProvider\QuestionDataProvider;
use App\DataProvider\ReportDataProvider;
use App\Dto\Reports\TotalEnquiryItemDto;
use App\Models\ActivityTrackerTotal;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use App\Models\VirtualExpo;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Psr\SimpleCache\InvalidArgumentException;
use RedisException;

class ReportsService
{
    private ReportDataProvider $dataProvider;
    private QuestionDataProvider $questionDataProvider;
    private AnswerDataProvider $answerDataProvider;

    public function __construct(ReportDataProvider $dataProvider, QuestionDataProvider $questionDataProvider, AnswerDataProvider $answerDataProvider)
    {
        $this->dataProvider = $dataProvider;
        $this->questionDataProvider = $questionDataProvider;
        $this->answerDataProvider = $answerDataProvider;
    }

    public function getEnquiriesToTimeMerchant(User $user): array
    {
        $items = $this->dataProvider->getEnquiriesToTimeMerchant(!$user->hasRole(Role::ROLE_ADMIN_SLUG) ? $user : null);

        return [
            'labels' => array_column($items, 'time_diff'),
            'datasets' => [
                [
                    'data' => array_column($items, 'qty'),
                    'borderColor' => '#3e95cd',
                    'fill' => false,
                ],
            ],
        ];
    }

    public function getEnquiriesToTimeContractor(User $user = null): array
    {
        $items = $this->dataProvider->getEnquiriesToTimeContract($user);

        return [
            'labels' => array_column($items, 'answersQty'),
            'datasets' => [
                [
                    'data' => array_column($items, 'questionsQty'),
                    'borderColor' => '#3e95cd',
                    'fill' => false,
                ],
            ],
        ];
    }

    public function getTotalContractorOnEnquiries(User $user): array
    {
        $data = [
            !$user->hasRole(Role::ROLE_ADMIN_SLUG) ? $this->questionDataProvider->getTotalEnquiriesSend($user) : $this->questionDataProvider->getTotalEnquiriesSendAll(),
            $this->questionDataProvider->getMerchantsReceived(!$user->hasRole(Role::ROLE_ADMIN_SLUG) ? $user : null),
            $this->answerDataProvider->getQuotesTotalForContractor(!$user->hasRole(Role::ROLE_ADMIN_SLUG) ? $user : null),
            $this->answerDataProvider->getQuotesAcceptedForContractor(!$user->hasRole(Role::ROLE_ADMIN_SLUG) ? $user : null),
        ];

        $labels = [
            'Enquiries sent (' . $data[0] . ')',
            'Merchants received (' . $data[1] . ')',
            'Quotes received (' . $data[2] . ')',
            'Quotes accepted (' . $data[3] . ')',
        ];

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'minBarLength' => 6,
                    'label' => '',
                    'backgroundColor' => '#e4e6f9',
                    'borderColor' => '#3d45c0',
                    'data' => $data,
                ],
            ],
        ];
    }

    public function getTotalSupplierOnEnquiries(User $user): array
    {
        $data = [
            $this->questionDataProvider->getTotalEnquiries(!$user->hasRole(Role::ROLE_ADMIN_SLUG) ? $user : null),
            $this->questionDataProvider->getMatchedEnquiries(!$user->hasRole(Role::ROLE_ADMIN_SLUG) ? $user : null),
            $this->answerDataProvider->getQuotesTotalForMerchant(!$user->hasRole(Role::ROLE_ADMIN_SLUG) ? $user : null),
            $this->answerDataProvider->getQuotesAcceptedForMerchant(!$user->hasRole(Role::ROLE_ADMIN_SLUG) ? $user : null),
        ];

        $labels = [
            'All enquiries (' . $data[0] . ')',
            'Matched enquiries (' . $data[1] . ')',
            'Quotes sent (' . $data[2] . ')',
            'Quotes accepted (' . $data[3] . ')',
        ];

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => '',
                    'backgroundColor' => '#e4e6f9',
                    'borderColor' => '#3d45c0',
                    'data' => $data,
                ],
            ],
        ];
    }

    /**
     * @throws RedisException
     * @throws InvalidArgumentException
     */
    public function getTotalEnquiries(int $branchId): array
    {
        $result = $this->dataProvider->getTotalEnquiries($branchId);

        $labels = array_map(static function (TotalEnquiryItemDto $item): string {
            return $item->getMonth();
        }, $result);

        $data = array_map(static function (TotalEnquiryItemDto $item): int {
            return $item->getTotal();
        }, $result);

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Enquiries',
                    'backgroundColor' => '#e4e6f9',
                    'borderColor' => '#3d45c0',
                    'data' => $data,
                ],
            ],
        ];
    }

    public function categoriesPercentageSelectedForMerchant(User $user): array
    {
        $items = $this->questionDataProvider->categoriesPercentagesSelectedMerchant(!$user->hasRole(Role::ROLE_ADMIN_SLUG) ? $user : null);

        $map = [];
        foreach ($items as $item) {
            $map[$item->name] = $item->total;
        }

        return $map;
    }

    public function categoriesPercentageSelectedForContractor(User $user): array
    {
        $items = $this->questionDataProvider->categoriesPercentagesSelectedContractor(!$user->hasRole(Role::ROLE_ADMIN_SLUG) ? $user : null);

        $map = [];
        foreach ($items as $item) {
            $map[$item->name] = $item->total;
        }

        return $map;
    }

    public function getSupplierReport(User $user): array
    {
        return $this->dataProvider->getSupplierReport($user);
    }

    public function getLogisticsReport(User $user): array
    {
        return $this->dataProvider->getLogisticsReport($user);
    }

    /**
     * @throws InvalidArgumentException
     * @throws RedisException
     */
    public function getBranchPerformance(int $branchId): array
    {
        $enquiries = $this->dataProvider->getTotalEnquiries($branchId);
        $quotes = $this->dataProvider->getTotalQuotes($branchId);

        $totals = [];
        /** @var TotalEnquiryItemDto $enquiry */
        foreach ($enquiries as $enquiry) {
            $totals[$enquiry->getMonth()]['enquiry'] = $enquiry->getTotal();
        }

        /** @var TotalEnquiryItemDto $quote */
        foreach ($quotes as $quote) {
            $totals[$quote->getMonth()]['quote'] = $quote->getTotal();
        }

        return [
            'tooltips' => array_values(array_map(static function (array $item) {
                $enquiry = $item['enquiry'] ?? 0;
                if (0 === $enquiry) {
                    return '0/0';
                }

                return ($item['quote'] ?? 0) . '/' . $enquiry;
            }, $totals)),
            'labels' => array_keys($totals),
            'datasets' => [
                [
                    'label' => 'Branch Performance',
                    'backgroundColor' => ['red', 'green', 'blue', 'yellow', 'red', 'green', 'blue', 'yellow', 'red', 'green', 'blue', 'yellow'],
                    'data' => array_values(array_map(static function (array $item) {
                        $enquiry = $item['enquiry'] ?? 0;
                        if (0 === $enquiry) {
                            return 0;
                        }

                        return (($item['quote'] ?? 0) * 100) / $enquiry;
                    }, $totals)),
                ],
            ],
        ];
    }

    /**
     * @return array []TotalEnquiryItemDto
     * @throws InvalidArgumentException|RedisException
     */
    public function getTotalQuotes(int $branchId): array
    {
        $result = $this->dataProvider->getTotalQuotes($branchId);

        $labels = array_map(static function (TotalEnquiryItemDto $item): string {
            return $item->getMonth();
        }, $result);

        $data = array_map(static function (TotalEnquiryItemDto $item): int {
            return $item->getTotal();
        }, $result);

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Quotes',
                    'backgroundColor' => '#3d45c0',
                    'data' => $data,
                ],
            ],
        ];
    }

    public function getVirtualExpoReportAll(int $userId, array $manufacturers, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $query = VirtualExpo::query();
        $query->where([
            'is_active' => true,
        ]);
        if ($manufacturers) {
            $query->whereIn('user_id', $manufacturers);
        }
        if ($dateFrom) {
            $query->whereRaw('created_at <= ?', [$dateFrom]);
        }
        $virtualExpos = $query->get();

        $totalBannerImpressions = 0;
        $totalBannerClicks = 0;
        $totalDocumentDownloads = 0;
        $totalVideoViews = 0;
        $impressionsByBannerLocation = [];
        $clicksByBannerLocation = [];
        $impressionsByBannerUserType = [];
        $clicksByBannerUserType = [];

        foreach ($virtualExpos as $expo) {
            $totalBannerImpressions += $this->dataProvider->getTotalBannerImpressionsForExpoId($expo->id, $dateFrom, $dateTo);
            $totalBannerClicks += $this->dataProvider->getTotalBannerClicksForExpoId($expo->id, $dateFrom, $dateTo);
            $totalDocumentDownloads += $this->dataProvider->getTotalDocumentDownloadsExpoId($expo->id, $dateFrom, $dateTo);
            $totalVideoViews += $this->dataProvider->getTotalVideoViewsForExpoId($expo->id, $dateFrom, $dateTo);
            $impressionsByBannerLocationForExpo = $this->dataProvider->getTotalBannerImpressionsByLocationForExpoId($expo->id, $dateFrom, $dateTo);
            $clicksByBannerLocationForExpo = $this->dataProvider->getTotalBannerClicksByLocationForExpoId($expo->id, $dateFrom, $dateTo);
            foreach ($impressionsByBannerLocationForExpo as $k => $v) {
                if (!isset($impressionsByBannerLocation[$k])) {
                    $impressionsByBannerLocation[$k] = 0;
                }
                $impressionsByBannerLocation[$k] += $v;
            }
            foreach ($clicksByBannerLocationForExpo as $k => $v) {
                if (!isset($clicksByBannerLocation[$k])) {
                    $clicksByBannerLocation[$k] = 0;
                }
                $clicksByBannerLocation[$k] += $v;
            }

            $impressionsByBannerUserTypeForExpo = $this->dataProvider->getTotalBannerImpressionsByUserTypeForExpoId($expo->id, $dateFrom, $dateTo);
            $clicksByBannerUserTypeForExpo = $this->dataProvider->getTotalBannerClicksByUserTypeForExpoId($expo->id, $dateFrom, $dateTo);
            foreach ($impressionsByBannerUserTypeForExpo as $k => $v) {
                if (!isset($impressionsByBannerUserType[$k])) {
                    $impressionsByBannerUserType[$k] = 0;
                }
                $impressionsByBannerUserType[$k] += $v;
            }
            foreach ($clicksByBannerUserTypeForExpo as $k => $v) {
                if (!isset($clicksByBannerUserType[$k])) {
                    $clicksByBannerUserType[$k] = 0;
                }
                $clicksByBannerUserType[$k] += $v;
            }
        }

        $items = [
            ['Total banner impressions', $totalBannerImpressions],
            ['Total banner clicks', $totalBannerClicks],
            ['Total document downloads', $totalDocumentDownloads],
            ['Total video views', $totalVideoViews],
        ];

        $impressionsByBannerLocationPrepared = [];
        $clicksByBannerLocationPrepared = [];

        $impressionsByBannerLocationPrepared[] = ['Impressions by banner location', ''];
        foreach ($impressionsByBannerLocation as $k => $v) {
            $impressionsByBannerLocationPrepared[] = [$k, $v];
        }
        $clicksByBannerLocationPrepared[] = ['Clicks by banner location', ''];
        foreach ($clicksByBannerLocation as $k => $v) {
            $clicksByBannerLocationPrepared[] = [$k, $v];
        }

        $items = array_merge($items, $impressionsByBannerLocationPrepared);
        $items = array_merge($items, $clicksByBannerLocationPrepared);

        $impressionsByBannerByUserTypePrepared = [];
        $clicksByBannerByUserTypePrepared = [];

        $impressionsByBannerByUserTypePrepared[] = ['A breakdown of impressions by user type', ''];
        foreach ($impressionsByBannerUserType as $k => $v) {
            $impressionsByBannerByUserTypePrepared[] = [$k, $v];
        }
        $clicksByBannerByUserTypePrepared[] = ['A breakdown of clicks by user type', ''];
        foreach ($clicksByBannerUserType as $k => $v) {
            $clicksByBannerByUserTypePrepared[] = [$k, $v];
        }

        $items = array_merge($items, $impressionsByBannerByUserTypePrepared);
        $items = array_merge($items, $clicksByBannerByUserTypePrepared);

        return [
            'items' => $items,
        ];
    }

    public function getVirtualExpoReport(int $userId, int $expoId, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $totalBannerImpressions = $this->dataProvider->getTotalBannerImpressionsForExpoId($expoId, $dateFrom, $dateTo);
        $totalBannerClicks = $this->dataProvider->getTotalBannerClicksForExpoId($expoId, $dateFrom, $dateTo);
        $totalDocumentDownloads = $this->dataProvider->getTotalDocumentDownloadsExpoId($expoId, $dateFrom, $dateTo);
        $totalVideoViews = $this->dataProvider->getTotalVideoViewsForExpoId($expoId, $dateFrom, $dateTo);

        $impressionsByBannerLocation = $this->dataProvider->getTotalBannerImpressionsByLocationForExpoId($expoId, $dateFrom, $dateTo);
        $clicksByBannerLocation = $this->dataProvider->getTotalBannerClicksByLocationForExpoId($expoId, $dateFrom, $dateTo);

        $impressionsByBannerUserType = $this->dataProvider->getTotalBannerImpressionsByUserTypeForExpoId($expoId, $dateFrom, $dateTo);
        $clicksByBannerUserType = $this->dataProvider->getTotalBannerClicksByUserTypeForExpoId($expoId, $dateFrom, $dateTo);

        $items = [
            ['Total banner impressions', $totalBannerImpressions],
            ['Total banner clicks', $totalBannerClicks],
            ['Total document downloads', $totalDocumentDownloads],
            ['Total video views', $totalVideoViews],
        ];

        $impressionsByBannerLocationPrepared = [];
        $clicksByBannerLocationPrepared = [];

        $impressionsByBannerLocationPrepared[] = ['Impressions by banner location', ''];
        foreach ($impressionsByBannerLocation as $k => $v) {
            $impressionsByBannerLocationPrepared[] = [$k, $v];
        }
        $clicksByBannerLocationPrepared[] = ['Clicks by banner location', ''];
        foreach ($clicksByBannerLocation as $k => $v) {
            $clicksByBannerLocationPrepared[] = [$k, $v];
        }

        $items = array_merge($items, $impressionsByBannerLocationPrepared);
        $items = array_merge($items, $clicksByBannerLocationPrepared);

        $impressionsByBannerByUserTypePrepared = [];
        $clicksByBannerByUserTypePrepared = [];

        $impressionsByBannerByUserTypePrepared[] = ['A breakdown of impressions by user type', ''];
        foreach ($impressionsByBannerUserType as $k => $v) {
            $impressionsByBannerByUserTypePrepared[] = [$k, $v];
        }

        $clicksByBannerByUserTypePrepared[] = ['A breakdown of clicks by user type', ''];
        foreach ($clicksByBannerUserType as $k => $v) {
            $clicksByBannerByUserTypePrepared[] = [$k, $v];
        }

        $items = array_merge($items, $impressionsByBannerByUserTypePrepared);
        $items = array_merge($items, $clicksByBannerByUserTypePrepared);

        return [
            'items' => $items,
        ];
    }

    public function generateQuotesComparisonSpreadsheet($quoteIds): void
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        // Description, Qty, Unit Price
        //Merchant Name, Total
        $results = DB::table('activity_tracker as at')
            ->join('answers as a', 'a.id', '=', 'at.quote_id')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->where('at.type', 'App\\Models\\Answer')
            ->whereIn('a.id', $quoteIds)
            ->select('at.quote_id', 'at.amount', 'at.unit_price', 'at.unit', 'at.qty', 'at.desc', 'at.item_code', 'u.first_name')
            ->orderBy('a.id')
            ->get();
        $items = [];
        foreach ($results as $result) {
            $items[$result->quote_id][] = $result;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        $colIndex = 1;
        $row = 1;

        $totals = [];
        $maxRow = 1;
        foreach ($items as $quoteId => $quoteItems) {
            $merchant = $quoteItems[0]->first_name;

            $worksheet->setCellValue($this->colLetter($colIndex) . '1', $merchant . ' Description');
            $worksheet->setCellValue($this->colLetter($colIndex + 1) . '1', $merchant . ' Qty');
            $worksheet->setCellValue($this->colLetter($colIndex + 2) . '1', $merchant . ' Unit Price');
            $worksheet->setCellValue($this->colLetter($colIndex + 3) . '1', $merchant . ' Unit');

            $total = 0;
            $row = 2;
            foreach ($quoteItems as $item) {
                $unitPrice = (float)preg_replace('/[^0-9\.]+/', '', $item->unit_price ?? '');
                $worksheet->setCellValue($this->colLetter($colIndex) . $row, $item->desc ?? $item->item_code);
                $worksheet->setCellValue($this->colLetter($colIndex + 1) . $row, $item->qty);
                $worksheet->setCellValue($this->colLetter($colIndex + 2) . $row, $unitPrice);
                $worksheet->setCellValue($this->colLetter($colIndex + 3) . $row, $item->unit ?? '');
                $row++;

                $total += $unitPrice * (int)$item->qty;

                $maxRow = max($maxRow, $row);
            }

            $totalRecord = ActivityTrackerTotal::where([
                'quote_id' => $quoteId,
            ])->first();
            if ($totalRecord && $totalRecord->total > 0) {
                $total = $totalRecord->total;
            }

            $totals[] = [
                'Merchant' => $merchant,
                'Total' => $total,
            ];

            $colIndex += 3;
        }

        $row = $maxRow + 3;
        $worksheet->setCellValue($this->colLetter(1) . $row, 'Merchant Name');
        $worksheet->setCellValue($this->colLetter(2) . $row, 'Total');

        foreach ($totals as $total) {
            $row++;
            $worksheet->setCellValue($this->colLetter(1) . $row, $total['Merchant']);
            $worksheet->setCellValue($this->colLetter(2) . $row, $total['Total']);
        }


        $writer->save('php://output');
    }

    public function colLetter($colNum): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colNum);
    }

    public function getBuyerReport(User $user, int $projectId, int $worksPackageId): array
    {
        return $this->dataProvider->getBuyerReport($user, $projectId, $worksPackageId);
    }

    public function getContractorReport(User $user, int $projectId, int $worksPackageId): array
    {
        return $this->dataProvider->getContractorReport($user, $projectId, $worksPackageId);
    }

    public function getContractorMapData(?int $radius, array $type, int $projectId, array $worksPackageIds, User $user): array
    {
        return $this->dataProvider->getContractorMapData($radius, $type, $projectId, $worksPackageIds, $user);
    }

    public function generateQuotesSpreadsheet(Question $question, LengthAwarePaginator $answers): void
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $worksheet->setCellValue('A1', 'Enquiry ID');
        $worksheet->setCellValue('B1', 'Created Date');
        $worksheet->setCellValue('C1', 'Due Date');
        $worksheet->setCellValue('D1', 'Postcode');
        $worksheet->setCellValue('E1', 'Product Category');
        $worksheet->setCellValue('F1', 'Live/Tender');
        $worksheet->setCellValue('G1', 'Comments');
        $worksheet->setCellValue('H1', 'Works Package');
        $worksheet->setCellValue('I1', 'Project Name');

        $worksheet->setCellValue('A2', $question->id);
        $worksheet->setCellValue('B2', $question->created_at);
        $worksheet->setCellValue('C2', $question->days);
        $worksheet->setCellValue('D2', $question->postcode);
        $worksheet->setCellValue('E2', $question->getCategory());
        $worksheet->setCellValue('F2', $question->type);
        $worksheet->setCellValue('G2', $question->comment);
        $worksheet->setCellValue('H2', $question->worksPackage->getName());
        $worksheet->setCellValue('I2', $question->project->getName());

        if (!empty($answers)) {
            $worksheet->setCellValue('A4', 'Quotes:');
            $worksheet->setCellValue('A5', 'Name');
            $worksheet->setCellValue('B5', 'Full/Part');
            $worksheet->setCellValue('C5', 'Price (£)');
            $worksheet->setCellValue('D5', 'Comments');
            $worksheet->setCellValue('E5', 'Accepted At');
            $worksheet->setCellValue('F5', 'Supplier Invoice No.');
            $worksheet->setCellValue('G5', 'ESG Saving');

            $i = 6;

            foreach ($answers as $answer) {
                $worksheet->setCellValue("A{$i}", $answer['first_name']);
                $worksheet->setCellValue("B{$i}", $answer['type']);
                $worksheet->setCellValue("C{$i}", $answer['price']);
                $worksheet->setCellValue("D{$i}", $answer['comment']);
                $worksheet->setCellValue("E{$i}", $answer['quote_accepted_at']);
                $worksheet->setCellValue("F{$i}", $answer['supplier_invoice_no']);
                $worksheet->setCellValue("G{$i}", round($answer['esgPerc'], 2) . '%');

                $i++;
            }
        }

        // auto size columns
        foreach ($worksheet->getColumnIterator() as $column) {
            $worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        // format all columns as text
        $worksheet->getStyle('A:I')->getNumberFormat()->setFormatCode('@');

        // format headers
        $worksheet->getStyle('A1:I1')->getFont()->setBold(true);
        $worksheet->getStyle('A5:I5')->getFont()->setBold(true);
        $worksheet->getStyle('A1:I1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('f1f1f1');
        $worksheet->getStyle('A5:I5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('f1f1f1');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }

    public function generateQuoteComparisonSpreadsheet($data): void
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $worksheet->setCellValue('A1', 'Summary');
        $worksheet->setCellValue('A2', 'Total 1');
        $worksheet->setCellValue('B2', 'Total 2');
        $worksheet->setCellValue('C2', 'Total 3');
        $worksheet->setCellValue('D2', 'Savings');
        $worksheet->setCellValue('E2', 'Percentage Saved');

        $worksheet->setCellValue('A3', $data['summary']['total_1']);
        $worksheet->setCellValue('B3', $data['summary']['total_2']);
        $worksheet->setCellValue('C3', $data['summary']['total_3']);
        $worksheet->setCellValue('D3', $data['summary']['savings']);
        $worksheet->setCellValue('E3', $data['summary']['percentage_saved']);

        $worksheet->setCellValue('A5', 'Products');
        $worksheet->setCellValue('A6', 'Product Name');
        $worksheet->setCellValue('B6', 'Quote No.');
        $worksheet->setCellValue('C6', 'Quantity');
        $worksheet->setCellValue('D6', 'Unit Price');
        $worksheet->setCellValue('E6', 'Total');

        $rowNumber = 7;

        foreach ($data['products'] as $product) {
            $worksheet->setCellValue('A' . $rowNumber, $product['product_name']);

            foreach ($product['product_data'] as $index => $pdata) {
                $worksheet->setCellValue('B' . $rowNumber, ($index + 1));
                $worksheet->setCellValue('C' . $rowNumber, $pdata['qty']);
                $worksheet->setCellValue('D' . $rowNumber, $pdata['unit_price']);
                $worksheet->setCellValue('E' . $rowNumber, $pdata['total']);
                $rowNumber++;
            }
        }

        // auto size columns
        foreach ($worksheet->getColumnIterator() as $column) {
            $worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        // format all columns as text
        $worksheet->getStyle('A:E')->getNumberFormat()->setFormatCode('@');

        // format headers
        $worksheet->getStyle('A1:E1')->getFont()->setBold(true);
        $worksheet->getStyle('A2:E2')->getFont()->setBold(true);
        $worksheet->getStyle('A5:E5')->getFont()->setBold(true);
        $worksheet->getStyle('A6:E6')->getFont()->setBold(true);
        $worksheet->getStyle('A1:E1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('f1f1f1');
        $worksheet->getStyle('A2:E2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('f1f1f1');
        $worksheet->getStyle('A5:E5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('f1f1f1');
        $worksheet->getStyle('A6:E6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('f1f1f1');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }

    public function getSubcontractorReport(User $user, array $subcontractorIds): array
    {
        return $this->dataProvider->getSubcontractorReport($user, $subcontractorIds);
    }

    public function getUserSummary(User $user): array
    {
        return $this->dataProvider->getUserSummary($user);
    }
}
