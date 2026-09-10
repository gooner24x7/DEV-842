<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ManufacturerImportedProduct;
use Illuminate\Console\Command;

class MarshIndustriesImportProducts extends Command
{
    protected $signature = 'import:marsh-products {manufacturer-id} {file?}';
    protected $description = 'Import list of products for the given manufacturer';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $manufacturerId = $this->argument('manufacturer-id');
        $file = $this->argument('file') ?? '/Product Data May 24 Marsh_.csv';

        $file = fopen(__DIR__ . $file, 'r');

        fgetcsv($file, 10000, ",");
        fgetcsv($file, 10000, ",");

        while (($row = fgetcsv($file, 10000, ",")) !== false) {
            ManufacturerImportedProduct::create([
                'manufacturer_id' => $manufacturerId,
                'deal_id' => 0,
                'name' => $row[1] ?? null,
                'category_name' => $row[2] ?? null,
                'subcategory_name' => $row[3] ?? null,
                'supplier_product_code' => $row[12] ?? null,
                'environmental_text' => $row[16] ?? null,
                'colour' => $row[18] ?? null,
                'feature1' => $row[20] ?? null,
                'feature2' => $row[21] ?? null,
                'width_m' => $row[6] ?? null,
                'length_m' => $row[5] ?? null,
                'depth_m' => $row[7] ?? null,
                'weight_kg' => $row[8] ?? null,
                'list_price' => $row[9] ?? null,
                'discount_percent' => $row[10] ?? null,
                'direct2_cost_price' => $row[11] ?? null,
                'sku' => $row[13] ?? null,
                'lead_time_in_days' => $row[14] ?? null,
                'ean' => $row[15] ?? null,
                'product_manufacturer' => $row[17] ?? null,
                'unit_of_measure' => $row[19] ?? null,
            ]);
        }

        fclose($file);
    }
}
