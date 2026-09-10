<?php
declare(strict_types=1);

namespace App\Repository;

use Illuminate\Support\Facades\DB;

class PostcodesRepository
{
    public function getDistrict(string $postcode): ?object
    {
        return DB::table('Postcode_districts')->where('Postcode', $postcode)->first();
    }

    public static function preparePostcode($p): string
    {
        return trim(substr(trim($p), 0, -3));
    }

    public function getCoords(array $postcodes): array
    {
        return DB::table('postcodes')
            ->select('postcode', 'latitude', 'longitude')
            ->whereIn('postcode', $postcodes)
            ->get()->map(fn ($item) => (array)$item)->toArray();
    }
}
