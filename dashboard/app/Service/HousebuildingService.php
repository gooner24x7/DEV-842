<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\HousebuildingProduct;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class HousebuildingService
{
    const CATEGORY_PRODUCT_IDS = [
        "Brickwork KH 2018(17)"             => 181,
        "Brick Reinforcement"               => 181,
        "Brickettes New 2018"               => 181,
        "Party Wall Ins Cav Socks 2018"     => 137,
        "Lintels - IG"                      => 166,
        "Joists"                            => 124,
        "Chipboard and Tape - Jewson"       => 137,
        "Roof Trusses"                      => 124,
        "Upvc fascias barge WHITE"          => 123,
        "Joiners sundries n ECO"            => 125,
        "First Fix Joiner Jewson"           => 125,
        "Sheet Materials"                   => 123,
        "Door Kits NH14 ladder"             => 125,
        "Stairs"                            => 125,
        "Insulation"                        => 137,
        "Second Fix Joiner JEWSON"          => 125,
        "Ironmongery"                       => 160
    ];

    const CATEGORY_TOTALS = [
        "Brickwork KH 2018(17)"                 => "Grand Total",
        "Brickettes New 2018"                   => "Grand Total",
        "Brick Reinforcement"                   => "Total Reinforcement",
        "Party Wall Ins Cav Socks 2018"         => "Sub Total Part Wall",
        "Cavity Trays"                          => "Sub Total Cavity Trays",
        "Lintels - IG"                          => "Total Lintels",
        "Joists"                                => "Total Joists",
        "Chipboard and Tape - Jewson"           => "Total Chipboard & Tape",
        "Expansion Jts"                         => "Subtotal Expansion Joints",
        "RSJ & Padstone"                        => "Total RSJ & Padstone",
        "Roof Timbers"                          => "Total Roof Timbers",
        "Roof Trusses"                          => "Total Roof Trusses",
        "Upvc fascias barge WHITE"              => "Total Fascias",
        "Joiners sundries n ECO"                => "Total Joiner Sundries",
        "GRP Canopy / Bay / Dormer"             => "Total GRP Canopy / Bay / Dormer",
        "Front Door, Rear Door & Garage Door"   => "Total Front Door, Rear Door & Garage Door",
        "First Fix Joiner Jewson"               => "Total First Fix",
        "Sheet Materials"                       => "Sub Total Sheet Materials",
        "Door Kits NH14 ladder"                 => "Total Doors",
        "Stairs"                                => "Total Stairs",
        "Insulation"                            => "Total Insulation",
        "Second Fix Joiner JEWSON"              => "Total 2nd fix",
        "Ironmongery"                           => "Total Ironmongery"
    ];

    public function __construct()
    {

    }

    public function export(string $houseName, string $category, int $questionId): string
    {
        $data = $this->getProducts($houseName, $category);

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $worksheet->setCellValue('A1', 'House Name: ' . $houseName);
        $worksheet->setCellValue('A2', 'ID');
        $worksheet->setCellValue('B2', 'Category');
        $worksheet->setCellValue('C2', 'Subcategory');
        $worksheet->setCellValue('D2', 'Product Name');
        $worksheet->setCellValue('E2', 'Product Ref');
        $worksheet->setCellValue('F2', 'Unit');
        $worksheet->setCellValue('G2', 'Quantity');
        $worksheet->setCellValue('H2', 'Price');

        $rowNumber = 3;

        foreach ($data as $product) {
            $worksheet->setCellValue('A' . $rowNumber, $product['id']);
            $worksheet->setCellValue('B' . $rowNumber, $product['category']);
            $worksheet->setCellValue('C' . $rowNumber, $product['subcategory']);
            $worksheet->setCellValue('D' . $rowNumber, $product['product_name']);
            $worksheet->setCellValue('E' . $rowNumber, $product['product_ref']);
            $worksheet->setCellValue('F' . $rowNumber, $product['uom']);
            $worksheet->setCellValue('G' . $rowNumber, $product['quantity']);
            $worksheet->setCellValue('H' . $rowNumber, $product['price']);
            $rowNumber++;
        }

        // auto size columns
        foreach ($worksheet->getColumnIterator() as $column) {
            $worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        // format columns
        $worksheet->getStyle('A:H')->getNumberFormat()->setFormatCode('@');
        $worksheet->getStyle("G3:G$rowNumber")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

        // format headers
        $worksheet->getStyle('A1')->getFont()->setBold(true);
        $worksheet->getStyle('A2:H2')->getFont()->setBold(true);

        $dir = storage_path("app/public/housebuilding/{$questionId}");
        $filename = "housebuilding-products.xlsx";

        // check directory exists
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save("$dir/$filename");

        return "$dir/$filename";
    }

    public function getProducts(string $houseName = null, string $category = null): array
    {
        $query = HousebuildingProduct::query();

        if (!empty($houseName)) {
            $query->where('house_name', '=', $houseName);
        }

        if (!empty($category)) {
            $query->where('category', '=', $category);
        }

        return $query->get()->toArray();
    }

    public function getHouseNames(string $category = null): array
    {
        $query = HousebuildingProduct::query()->select('house_name')->distinct();;

        if (!empty($category)) {
            $query->where('category', '=', $category);
        }

        return $query->get()->pluck('house_name')->toArray();
    }

    public function getCategories(string $houseName = null): array
    {
//        $query = HousebuildingProduct::query()->select('category')->distinct();
//
//        if (!empty($houseName)) {
//            $query->where('house_name', '=', $houseName);
//        }
//
//        return $query->get()->pluck('category')->toArray();

        return array_keys(self::CATEGORY_TOTALS);
    }

    public function getCategoryProductId(string $category): ?int
    {
        return !empty(self::CATEGORY_PRODUCT_IDS[$category]) ? self::CATEGORY_PRODUCT_IDS[$category] : null;
    }
}
