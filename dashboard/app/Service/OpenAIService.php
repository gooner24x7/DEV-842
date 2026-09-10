<?php
declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private Client $client;
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => 300.0, // 5 minutes
        ]);
    }

    /**
     * @throws \Exception
     */
    private function request($method, $endpoint, $params = []): \Psr\Http\Message\ResponseInterface
    {
        $response = $this->client->request($method, $endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . config('openai.key'),
                'Content-Type' => 'application/json',
            ],
            'json' => $params,
        ]);

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            $contents = $response->getBody()->getContents();
            Log::error('OpenAI error: ' . $contents);
            throw new \Exception('OpenAI error: ' . $contents);
        }

        return $response;
    }

    /**
     * @throws \Exception
     */
    public function getDataSummary(array $data, string $prompt): array
    {
        $response = $this->request('POST', 'chat/completions', [
            "model" => "gpt-4o-mini", // gpt-4o
            "messages" => [
                [
                    "role" => "system",
                    "content" => [["type" => "text", "text" => json_encode($data)]],
                ],
                [
                    "role" => "user",
                    "content" => [["type" => "text", "text" => "Take this buyer report and summarise it to enable the buyer to understand each area and how that is good for the project based on its parameters."]]
                ],
                [
                    "role" => "user",
                    "content" => [["type" => "text", "text" => "That is good but we need to extend the report based on the client and the frame work and bring in local spend"]]
                ],
                [
                    "role" => "user",
                    "content" => [["type" => "text", "text" => "when talking about the ESG score, this is not how you presume. This is more based on on the ESG score representing savings in Co2 omissions on the options that the sub-contractor had within the platform, this would be more based around closer to site for delivery of materials. Meaning the higher the score the closer that merchant is to site. Can you keep the same report but change Sustainability & ESG Performance area to suit"]]
                ],
                [
                    "role" => "user",
                    "content" => [["type" => "text", "text" => "now put all of that report together"]],
                ]
            ],
            "response_format" => ["type" => "text"],
            "temperature" => 0.5,
            "max_completion_tokens" => 2048
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @throws \Exception
     */
    public function createVectorStore(string $name): array
    {
        $response = $this->request('POST', 'vector_stores', [
            "name" => $name,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @throws \Exception
     */
    public function createVectorStoreFile(string $storeId, string $fileId): array
    {
        $endpoint = "vector_stores/{$storeId}/files";

        $response = $this->request('POST', $endpoint, [
            "file_id" => $fileId,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @throws GuzzleException
     */
    public function uploadFile($data, $filename): array
    {
        $response = $this->client->request('POST', 'files', [
            'headers' => [
                'Authorization' => 'Bearer ' . config('openai.key'),
//                'Content-Type' => 'multipart/form-data',
            ],
            'multipart' => [
                [
                    'name'     => 'file',
                    'contents' => $data,
                    'filename' => $filename,
                ],
                [
                    'name'     => 'purpose',
                    'contents' => 'user_data'
                ]
            ]
        ]);

        $contents = $response->getBody()->getContents();

        Log::info($contents);

        return json_decode($contents, true);
    }

    /**
     * @throws \Exception
     */
    public function getVectorStoreFiles($storeId): array
    {
        $endpoint = "https://api.openai.com/v1/vector_stores/{$storeId}/files";

        $response = $this->client->request('GET', $endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . config('openai.key'),
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @throws \Exception
     */
    public function createResponse(array $body): array
    {
        $response = $this->request('POST', 'responses', $body);
        $contents = $response->getBody()->getContents();

        Log::info($contents);

        return json_decode($contents, true);
    }
}
