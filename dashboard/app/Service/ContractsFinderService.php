<?php
declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Redis;

class ContractsFinderService
{
    private Client $client;
    private Redis $redis;

    const string SEARCH_NOTICES_CACHE = 'search_notices_%s_%s_%s_%s_%s_%s_%d_%d_%d_%s_%s_%s_%s_%s_%s_%s_%s_%s_%s_%s_%s_%s_%s';
    const int SEARCH_NOTICES_CACHE_TIMEOUT = 3600;

    public function __construct(Redis $redis)
    {
        $this->client = new Client([
            'base_uri' => 'https://www.contractsfinder.service.gov.uk/',
            'timeout' => 30.0,
        ]);

        $this->redis = $redis;
    }

    /**
     * @throws \Exception
     */
    public function searchNotices(array $params = []): array
    {
        $response = $this->client->request('GET', 'Published/Notices/OCDS/Search', [
            'http_errors' => false,
            'query' => $params
        ]);

        if ($response->getStatusCode() !== 200) {
            Log::error('Contracts Finder API error: ' . $response->getBody()->getContents());
            throw new \Exception('Contracts Finder API error: ' . $response->getBody()->getContents());
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @throws \Exception
     */
    public function searchNotices2(array $searchCriteria = [], int $size = 10, $useCache = true): array
    {
        $searchCriteriaF = [];

        foreach($searchCriteria as $key => $value) {
            if (is_array($value)) {
                $searchCriteriaF[$key] = implode(',', $value);
            } else {
                $searchCriteriaF[$key] = $value;
            }
        }

        $key = vsprintf(self::SEARCH_NOTICES_CACHE, $searchCriteriaF);
        $result = $this->redis->get($key);

        if ($useCache && $result) {
            return unserialize($result);
        }

        $response = $this->client->request('POST', 'api/rest/2/search_notices/json', [
            'http_errors' => false,
            'json' => [
                "searchCriteria" => $searchCriteria,
                "size" => $size
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            Log::error('Contracts Finder API error: ' . $response->getBody()->getContents());
            throw new \Exception('Contracts Finder API error: ' . $response->getBody()->getContents());
        }

        $result = json_decode($response->getBody()->getContents(), true);
        $this->redis->set($key, serialize($result), self::SEARCH_NOTICES_CACHE_TIMEOUT);

        return $result;
    }

    /**
     * @throws \Exception
     */
    public function getNotice(string $id): array
    {
        $url = "Published/Notice/releases/{$id}.json";

        $response = $this->client->request('GET', $url);

        if ($response->getStatusCode() !== 200) {
            Log::error('Contracts Finder API error: ' . $response->getBody()->getContents());
            throw new \Exception('Contracts Finder API error: ' . $response->getBody()->getContents());
        }

        return json_decode($response->getBody()->getContents(), true);
    }

}
