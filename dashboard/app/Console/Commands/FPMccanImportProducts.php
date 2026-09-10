<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ManufacturerImportedProduct;
use Illuminate\Console\Command;

class FPMccanImportProducts extends Command
{
    protected $signature = 'import:fpmccan-products {manufacturer-id}';
    protected $description = 'Import list of products for the given manufacturer';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $manufacturerId = $this->argument('manufacturer-id');

        $file = fopen(__DIR__ . '/fpmccan-pim-marketing.csv', 'r');

        $columns = fgetcsv($file, 10000, ",");

        while (($row = fgetcsv($file, 10000, ",")) !== false) {
            ManufacturerImportedProduct::create([
                'manufacturer_id' => $manufacturerId,
                'deal_id' => $row[0] ?? null,
                'name' => $row[8] ?? null,
                'deal_name' => $row[1] ?? null,
                'category_name' => $row[2] ?? null,
                'subcategory_name' => $row[3] ?? null,
                'supplier_name' => $row[4] ?? null,
                'supplier_product_code' => $row[5] ?? null,
                'mpn' => $row[7] ?? null,
                'supplier_category_name' => $row[9] ?? null,
                'supplier_subcategory_name' => $row[10] ?? null,
                'web_product_name' => $row[12] ?? null,
                'etim_class_no' => $row[13] ?? null,
                'colour' => $row[14] ?? null,
                'material' => $row[15] ?? null,
                'finish' => $row[16] ?? null,
                'attr1_title' => $row[17] ?? null,
                'attr1_value' => $row[18] ?? null,
                'attr2_title' => $row[19] ?? null,
                'attr2_value' => $row[20] ?? null,
                'attr3_title' => $row[21] ?? null,
                'attr3_value' => $row[22] ?? null,
                'attr4_title' => $row[23] ?? null,
                'attr4_value' => $row[24] ?? null,
                'attr5_title' => $row[25] ?? null,
                'attr5_value' => $row[26] ?? null,
                'feature1' => $row[27] ?? null,
                'feature2' => $row[28] ?? null,
                'feature3' => $row[29] ?? null,
                'feature4' => $row[30] ?? null,
                'feature5' => $row[31] ?? null,
                'environmental_text' => $row[32] ?? null,
                'keywords' => $row[33] ?? null,
                'marketing_completeness' => $row[36] ?? null,
                'image_url' => $row[37] ?? null,
            ]);
        }

        fclose($file);
    }
}
