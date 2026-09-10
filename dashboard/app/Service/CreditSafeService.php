<?php
declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\SimpleCache\InvalidArgumentException;

class CreditSafeService
{
    const string TOKEN_KEY = 'CREDITSAFE_TOKEN';
    private string|null $token = null;
    private CacheRepository $cache;
    private Client $client;

    /**
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;
        $this->client = new Client([
            'base_uri' => 'https://connect.sandbox.creditsafe.com/v1/',
            'timeout' => 10.0,
        ]);
    }

    /**
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function getToken(): string|null
    {
        try {
            $token = $this->cache->get(self::TOKEN_KEY);
        } catch (InvalidArgumentException $e) {
            $token = null;
        }

        if (!$token) {
            $token = $this->authenticate();

            $this->cache->set(self::TOKEN_KEY, $token, 3600);
        }

        $this->token = $token;

        return $token;
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    private function authenticate(): string
    {
        $response = $this->client->post('authenticate', [
            'json' => [
                "username" => env('CREDIT_SAFE_USERNAME'), //config('credit_safe.username')
                "password" => env('CREDIT_SAFE_PASSWORD'),
            ],
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);

        $token = json_decode($response->getBody()->getContents(), true)['token'] ?? null;
        if (!$token) {
            throw new \Exception('Failed to retrieve token');
        }

        return $token;
    }

    /**
     * @throws GuzzleException
     */
    public function searchCompanies(string $regNo): array
    {
        $response = $this->client->get('companies', [
            'query' => [
                'countries' => 'GB',
                'regNo' => $regNo,
            ],
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @throws GuzzleException
     */
    public function getCreditReport(string $companyId): array
    {
        $response = $this->client->get('companies/' . $companyId, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
