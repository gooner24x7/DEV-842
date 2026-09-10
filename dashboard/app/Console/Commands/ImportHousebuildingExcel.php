<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\HousebuildingBudget;
use App\Models\HousebuildingProduct;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;

class ImportHousebuildingExcel extends Command
{
    protected $signature = 'import:housebuilding-excel';
    protected $description = 'Import housebuilding data from excel spreadsheet';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $fileType = 'Xlsx';
        $fileName = storage_path('housebuilding2.xlsx');
        $sheetName = 'Schedule';

        $reader = IOFactory::createReader($fileType);

        $reader->setReadDataOnly(false);
        $reader->setLoadSheetsOnly($sheetName);

        $spreadsheet = $reader->load($fileName);
        $houseNames = $this->getHouseNames($spreadsheet);
        $categories = $this->getCategories($spreadsheet);
        $data = $this->processWithIterator($spreadsheet, $houseNames);

        $this->insertProducts($data);
        $this->insertBudgets($houseNames, $categories);
    }

    private function process(Spreadsheet $spreadsheet, array $houseNames): array
    {
        $rows = $spreadsheet->getActiveSheet()->toArray();
        $output = [];
        $category = null;
        $categoryIndex = null;
        $subcategory = null;

        foreach ($rows as $index => $row) {
            $firstValue = $row[0];

            // skip rows where first value is empty or contains 'total'
            if (empty($firstValue) || str_contains(strtolower($firstValue), 'total')) {
                continue;
            }

            // check if its a category header
            if (str_contains($firstValue, 'Category:')) {
                $category = explode('Category:', $firstValue)[1];
                $categoryIndex = $index;
                continue;
            }

            // todo: check if its a subcategory header (italics)

            // store the row in output array
            // house data (quantity values) begins at $row[4]
            // data template: [house_name, category, subcategory, product_name, product_ref, uom, quantity, price)
             foreach($row as $colIndex => $value) {
                 $houseIndex = $colIndex - 4;

                 if ($colIndex < 4 || empty($houseNames[$houseIndex])) {
                     continue;
                 }

                 $houseName = $houseNames[$houseIndex];
                 $resultRow = [$houseName, $category, $subcategory, $firstValue, $row[2], $row[3], $value];

                 $output[] = $resultRow;

                 echo implode(', ', $resultRow) . PHP_EOL;
             }
        }

        return $output;
    }

    private function processWithIterator(Spreadsheet $spreadsheet, array $houseNames): array
    {
        $worksheet = $spreadsheet->getActiveSheet();
        $rowIterator = $worksheet->getRowIterator();
        $output = [];
        $category = null;
        $categoryIndex = null;
        $subcategory = null;

        foreach ($rowIterator as $index => $row) {
            $firstValue = $worksheet->getCell("A$index")->getValue();
            $firstValueFont = $worksheet->getCell("A$index")->getStyle()->getFont();

//            $italic = $firstValueFont->getItalic();
//            $bold = $firstValueFont->getBold();
//            $underline = $firstValueFont->getUnderline();
//
//            echo "A$index : italic=$italic, bold=$bold, underline=$underline" . PHP_EOL;
//            continue;

            $ref = $worksheet->getCell("C$index")->getValue();
            $uom = $worksheet->getCell("D$index")->getValue();

            // check if row contains at least one quantity value
            $rowHasQuantities = $this->rowHasQuantities($row);

            // skip rows where first value is empty or contains 'total'
            if ($row->isEmpty() || empty($firstValue) || str_contains(strtolower($firstValue), 'total') || $index === 1) {
                continue;
            }

            // check if its a category header
            if (str_contains($firstValue, 'Category:')) {
                $category = trim(explode('Category:', $firstValue)[1]);
                $categoryIndex = $index;
                $subcategory = null;

                continue;
            }

            // skip next row after category header
            if ($index === $categoryIndex + 1) {
                continue;
            }

            // check if its a subcategory header (italics, bold or underlined)
            if (($firstValueFont->getItalic() || $firstValueFont->getBold() || $firstValueFont->getUnderline() !== 'none') && !$rowHasQuantities) {
                $subcategory = $firstValue;
                continue;
            }

            // store the row in output array
            // house data (quantity values) begins at colIndex 5
            // data template: [house_name, category, subcategory, product_name, product_ref, uom, quantity, price)
            $columnIterator = $row->getCellIterator();

            foreach ($columnIterator as $key => $cell) {
                $value = $cell->getCalculatedValue();
                //$valueText = is_string($cell->getValue()) ? $cell->getValue() : $cell->getValue()->getPlainText();
                $colIndex = Coordinate::columnIndexFromString($key);
                $houseIndex = $colIndex - 5;

                if ($colIndex < 5 || empty($houseNames[$houseIndex])) {
                    continue;
                }

                // skip if quantity is not a number
                if (!is_numeric($value)) {
                    continue;
                }

                $houseName = $houseNames[$houseIndex];

                $resultRow = [
                    'house_name'    => $houseName,
                    'category'      => trim($category),
                    'subcategory'   => $subcategory,
                    'product_name'  => trim($firstValue),
                    'product_ref'   => $ref,
                    'uom'           => $uom,
                    'quantity'      => (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
                ];

                $output[] = $resultRow;

                //echo implode(', ', $resultRow) . PHP_EOL;
            }
        }

        return $output;
    }

    private function getHouseNames(Spreadsheet $spreadsheet): array
    {
        $names = $spreadsheet->getActiveSheet()
            ->rangeToArray(
                'E1:BZ1',     // The worksheet range that we want to retrieve
                NULL,        // Value that should be returned for empty cells
                TRUE,        // Should formulas be calculated (the equivalent of getCalculatedValue() for each cell)
                TRUE,        // Should values be formatted (the equivalent of getFormattedValue() for each cell)
                FALSE         // Should the array be indexed by cell row and cell column
            )[0];

        foreach ($names as &$name) {
            // remove excess whitespace
            $name = trim(preg_replace('/\s+/', ' ', $name));
        }

        return $names;
    }

    private function getCategories(Spreadsheet $spreadsheet): array
    {
        $worksheet = $spreadsheet->getActiveSheet();
        $rowIterator = $worksheet->getRowIterator();
        $categories = [];

        foreach ($rowIterator as $index => $row) {
            $firstValue = $worksheet->getCell("A$index")->getValue();

            if (!empty($firstValue) && str_contains($firstValue, 'Category:')) {
                $category = trim(explode('Category:', $firstValue)[1]);

                if (!in_array($category, $categories)) {
                    $categories[] = $category;
                }
            }
        }

        return $categories;
    }

    private function insertProductsBulk(array $data): void
    {
        try {
            HousebuildingProduct::insert($data);
        } catch(\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

//        $test = [$data[0], $data[1], $data[2], $data[3], $data[4]];
//        dd($test);
//        HousebuildingProduct::insert($test);
    }

    private function insertProducts(array $data): void
    {
        $total = count($data);
        $currentIndex = 0;
        $currentRow = [];

        try {
            foreach($data as $index => $row) {
                $currentIndex = $index;
                $currentRow = $row;
                HousebuildingProduct::create($row);
            }

            echo "$total rows inserted into housebuilding table" . PHP_EOL;
        } catch(\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            echo implode(', ', $currentRow) . PHP_EOL;
        }
    }

    private function insertBudgets(array $houseNames, array $categories): void
    {
        $total = 0;
        $types = [0, 1, 2]; // 0=budget, 1=total, 2=actual_price

        try {
            foreach($houseNames as $name) {
                foreach ($categories as $category) {
                    foreach($types as $type) {
                        HousebuildingBudget::create(['type' => $type, 'house_name' => $name, 'category_name' => $category]);
                        $total++;
                    }
                }

                // extra rows for house grand totals
                foreach($types as $type) {
                    HousebuildingBudget::create(['type' => $type, 'house_name' => $name, 'category_name' => 'Grand Totals']);
                    $total++;
                }
            }

            echo "$total rows inserted into housebuilding_budgets table" . PHP_EOL;
        } catch(\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    private function rowHasQuantities(Row $row): bool
    {
        $columnIterator = $row->getCellIterator('E', 'BZ');
        $hasQuantities = false;

        foreach($columnIterator as $key => $cell) {
            $value = $cell->getCalculatedValue();

            if (is_numeric($value)) {
                $hasQuantities = true;
            }
        }

        return $hasQuantities;
    }
}
