<?php

namespace App\Service;

use GuzzleHttp\Exception\GuzzleException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Redis;
use RedisException;

class ZohoReportService
{
    private ZohoCrmService $service;
    private Redis $redis;

    public function __construct(ZohoCrmService $service, Redis $redis)
    {
        $this->service = $service;
        $this->redis = $redis;
    }

    /**
     * @throws GuzzleException
     * @throws RedisException
     */
    public function getSettings()
    {
        $users = $this->service->getUsers();
        $userNames = array_map(function ($item) {
            return $item['full_name'] ?? '';
        }, $users);

        $allocateToStaff = [
            'Nationals' => [],
            'Regionals' => [],
            'Independents' => [],
            'Contractors' => [],
            'Manufacturers' => [],
            'Supplier Buying Groups' => [],
            'Industry Bodies' => [],
        ];

        $settings = unserialize($this->redis->get('reportSettingsMap'));
        if (!$settings) {
            foreach ($allocateToStaff as $type => $arr) {
                foreach ($userNames as $name) {
                    $allocateToStaff[$type][$name] = 0;
                }
            }

            return [
                'planToLaunch' => [["0", "0", "0"], ["0", "0", "0"], ["0", "0", "0"], ["0", "0", "0"], ["0", "0", "0"], ["0", "0", "0"], ["0", "0", "0"]],
                'allocateToStaff' => $allocateToStaff,
            ];
        }

        if (!isset($settings['allocateToStaff'])) {
            foreach ($allocateToStaff as $type => $arr) {
                foreach ($userNames as $name) {
                    $allocateToStaff[$type][$name] = 0;
                }
            }

            $settings['allocateToStaff'] = $allocateToStaff;
        }

        return $settings;
    }

    /**
     * @throws RedisException
     * @throws GuzzleException
     */
    public function outputReport($planToLaunch, $allocateToStaff): void
    {
        $contractors = $this->service->getContractors();
        $suppliers = $this->service->getSuppliers();
        $manufacturers = $this->service->getManufacturers();
        $buyingGroups = $this->service->getBuyingGroups();
        $industryBodies = $this->service->getIndustryBodies();

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
        $spreadsheet = $reader->load(storage_path('./report.xlsx'));

        $map = [];
        foreach ($suppliers as $i => $supplier) {
            $map[$supplier['Supplier_Coverage']][] = $supplier;
        }

        /** get active sheet */
        $spreadsheet->setActiveSheetIndex($spreadsheet->getFirstSheetIndex());
        $sheet = $spreadsheet->getActiveSheet();

        $this->redis->set('reportSettingsMap', serialize([
            'planToLaunch' => $planToLaunch,
            'allocateToStaff' => $allocateToStaff,
        ]));

        /** set allocation to Staff */
        $col = 23;
        $row = 22;
        $startTotals = 11;
        foreach ($allocateToStaff as $type => $allocations) {
            $row = $this->addAllocations($col, $row, $sheet, $type, $allocations, $startTotals);
            $startTotals++;
        }

        /** set default values top table */
        for ($col = 4; $col < 7; $col++) {
            for ($row = 8; $row < 15; $row++) {
                $sheet->setCellValue([$col, $row], $planToLaunch[($row - 8)][($col - 4)] ?? 0);
            }
        }

        $lastRow = $this->displayTable(19, $spreadsheet, $sheet, $map['National'] ?? [], 'National Suppliers', 8, 'e2f0d9');
        $sheet->setCellValue('L11', '=I' . (19 + 1));

        $sheet->setCellValue('L12', '=I' . ($lastRow + 2));
        $lastRow = $this->displayTable($lastRow + 1, $spreadsheet, $sheet, $map['Regional'] ?? [], 'Regional Suppliers', 9, 'dae3f3');

        $sheet->setCellValue('L13', '=I' . ($lastRow + 2));
        $lastRow = $this->displayTable($lastRow + 1, $spreadsheet, $sheet, $map['Independent'] ?? [], 'Independent Suppliers', 10, 'fbe5d6');

        $sheet->setCellValue('L14', '=I' . ($lastRow + 2));
        $lastRow = $this->displayTable($lastRow + 1, $spreadsheet, $sheet, $contractors, 'Contractors', 11, 'fff2cc');

        $sheet->setCellValue('L15', '=I' . ($lastRow + 2));
        $lastRow = $this->displayTable($lastRow + 1, $spreadsheet, $sheet, $manufacturers, 'Manufacturers', 12, 'dae3f3');

        $sheet->setCellValue('L16', '=I' . ($lastRow + 2));
        $lastRow = $this->displayTable($lastRow + 1, $spreadsheet, $sheet, $buyingGroups, 'Buying Groups', 13, 'fff2cc');

        $sheet->setCellValue('L17', '=I' . ($lastRow + 2));
        $lastRow = $this->displayTable($lastRow + 1, $spreadsheet, $sheet, $industryBodies, 'Industry Bodies', 14, 'dae3f3');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }

    public function addAllocations($col, $startRow, Worksheet $sheet, $type, $allocations, $startTotals): int
    {
        $row = $startRow;
        $sheet->getStyle([$col - 12, $row - 1])->getFont()->setBold(true);
        $sheet->setCellValue([$col - 12, $row - 1], $type);

        foreach ($allocations as $user => $value) {
            $sheet->setCellValue([$col - 12, $row], '=CONCAT("' . $user . ' (", W' . $row . ', "%)")');

            //formulas
            for ($c = $col - 11; $c <= $col; $c++) {
                $b = ord('L') + ($c - ($col - 11));
                $sheet->setCellValue([$c, $row], '=' . chr($b) . $startTotals . '*' . ($value / 100));
            }

            $sheet->setCellValue([$col, $row], $value);
            $row++;
        }

        $sheet->getStyle("K" . ($startRow - 1) . ":V" . ($row - 1))
            ->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_THIN)
            ->setColor(new Color('00000000'));

        $sheet->getStyle("K" . ($startRow - 1) . ":V" . ($row - 1))
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('dae3f3');

        $row += 2;
        return $row;
    }

    public function displayTable($row, Spreadsheet $spreadsheet, Worksheet $sheet, $items, $title, $col, $bgColor)
    {
        $sectionSheet = $spreadsheet->createSheet();
        $sectionSheet->setTitle($title);

        $startRow = $row;
        $startColumn = 2;
        $sheet->setCellValue([$startColumn, $startRow], $title);
        $startColumn += 2;

        $sheet->setCellValue([$startColumn++, $startRow], 'Signed Up');
        $sheet->setCellValue([$startColumn++, $startRow], 'In Discussion');
        $sheet->setCellValue([$startColumn++, $startRow], 'Expressed an interest');
        $sheet->setCellValue([$startColumn++, $startRow], 'Demo Complete');
        $sheet->setCellValue([$startColumn++, $startRow], 'Contract Sent');
        $sheet->setCellValue([$startColumn, $startRow], 'To Find');

        $startRow++;
        $startColumn = 2;
        $sheet->setCellValue([$startColumn, $startRow], 'Number');
        $startColumn += 2;

        $signedUpTotal = 0;
        $expressedAnInterest = 0;
        $inDiscussionTotal = 0;

        //Signed Up In Discussion
        $sectionSheet->setCellValue([1, 1], 'Signed Up');
        $sectionSheet->setCellValue([2, 1], 'In Discussion');
        $sectionSheet->setCellValue([3, 1], 'Expressed an interest');
        $sectionSheet->setCellValue([4, 1], 'Demo Complete');
        $sectionSheet->setCellValue([5, 1], 'Contract Sent');

        $rowC = 2;
        foreach ($items as $item) {
            if ($item['Sales_Status'] === 'Signed Up') {
                $sectionSheet->setCellValue([1, $rowC++], $item['Account_Name'] ?? '');
                $signedUpTotal++;
            }
        }
        //$maxRow = max($startRow, $rowC);

        $rowC = 2;
        foreach ($items as $item) {
            if ($item['Sales_Status'] === 'In Discussion') {
                $sectionSheet->setCellValue([2, $rowC++], $item['Account_Name'] ?? '');
                $inDiscussionTotal++;
            }
        }
        //$maxRow = max($maxRow, $rowC);
        $maxRow = $startRow + 1;

        $rowC = 2; //$startRow + 1;
        foreach ($items as $item) {
            if ($item['Sales_Status'] === 'Expressed an Interest') {
                $sectionSheet->setCellValue([3, $rowC++], $item['Account_Name'] ?? '');
                $expressedAnInterest++;
            }
        }

        $demoCompleteTotal = 0;
        $rowC = 2;
        foreach ($items as $item) {
            if ($item['Sales_Status'] === 'Demo Complete') {
                $sectionSheet->setCellValue([4, $rowC++], $item['Account_Name'] ?? '');
                $demoCompleteTotal++;
            }
        }

        $contractSent = 0;
        $rowC = 2; //$startRow + 1;
        foreach ($items as $item) {
            if ($item['Sales_Status'] === 'Contract Sent') {
                $sectionSheet->setCellValue([5, $rowC++], $item['Account_Name'] ?? '');
                $contractSent++;
            }
        }
        //$maxRow = max($maxRow, $rowC);

        /** totals found */
        $sheet->setCellValue([$startColumn++, $startRow], $signedUpTotal);
        $sheet->setCellValue([$startColumn++, $startRow], $inDiscussionTotal);
        $sheet->setCellValue([$startColumn++, $startRow], $expressedAnInterest);
        $sheet->setCellValue([$startColumn++, $startRow], $demoCompleteTotal);
        $sheet->setCellValue([$startColumn++, $startRow], $contractSent);
        $sheet->setCellValue(
            [$startColumn,
                $startRow],
            "=IF(D$startRow+E$startRow+F$startRow+G$startRow+H$startRow>D$col,0,D$col-D$startRow-E$startRow-F$startRow)"
        );

        $startRow++;
        $startColumn = 2;
        $sheet->setCellValue([$startColumn, $startRow + 1], 'Signed Up as % of Ideal');
        $sheet->setCellValue([$startColumn, $startRow + 2], 'Signed Up as % of Minimum National');
        $sheet->setCellValue([$startColumn, $startRow + 3], 'Signed Up as % of Yorkshire');

        $startColumn++;
        $rowT = $row + 1;
        $sheet->setCellValue([$startColumn, $startRow + 1], "=D$rowT/D$col");
        $sheet->setCellValue([$startColumn, $startRow + 2], "=D$rowT/E$col");
        $sheet->setCellValue([$startColumn, $startRow + 3], "=D$rowT/F$col");

        $lastRow = max($maxRow, $startRow + 4);

        $sheet->getStyle("B$row:I" . ($lastRow - 1))
            ->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_THIN)
            ->setColor(new Color('00000000'));

        $sheet->getStyle("B$row:I" . ($lastRow - 1))
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB($bgColor);

        return $lastRow;
    }

    public function getDeals() : array
    {
        return $this->service->getDeals();
    }

    /**
     * @throws GuzzleException
     */
    public function updateDeal($id, $data): bool
    {
        return $this->service->updateDeal($id, $data);
    }
}
