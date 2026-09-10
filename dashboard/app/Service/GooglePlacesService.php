<?php
declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;

class GooglePlacesService
{
    private string $api_key = "AIzaSyClDgDRQPP1XKgVK3XXQbK7hm07LLW2hrY";
    private int $ttl = 300;
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => '',
            'timeout' => 10.0,
        ]);
    }

    public function findPlace(string $searchText): array
    {
        $key = 'google.places.' . strtolower(str_replace(' ', '', $searchText));

        return Cache::remember($key, $this->ttl, function () use ($searchText) {
            $response = $this->client->get('https://maps.googleapis.com/maps/api/place/findplacefromtext/json', [
                'query' => [
                    'input' => $searchText,
                    'inputtype' => 'textquery',
                    'key' => $this->api_key,
                    'fields' => 'place_id,name,formatted_address,type'
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['places'] ?? [];
        });
    }

    public function findPlaceNew(string $searchText): array
    {
        $key = 'google.places.' . strtolower(str_replace(' ', '', $searchText));

        return Cache::remember($key, $this->ttl, function () use ($searchText) {
            $response = $this->client->post('https://places.googleapis.com/v1/places:searchText', [
                'body' => json_encode([
                    "textQuery" => $searchText
                ]),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Goog-Api-Key' => $this->api_key,
                    'X-Goog-FieldMask' => 'places.id,places.name,places.displayName,places.formattedAddress,places.nationalPhoneNumber'
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['places'] ?? [];
        });
    }
}
