<?php
declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ZohoCrmService
{
    private ZohoOauthCustom $zohoOAuth;
    private Client $client;
    private string $supplierDataViewId = '465109000001395125';
    private string $contractorDataViewId = '465109000001395136';
    private string $manufacturerDataViewId = '465109000001395147';
    private string $buyingGroupsId = '465109000001395158';
    private string $industryBodiesId = '465109000001395169';

    public function __construct(ZohoOauthCustom $zohoOAuth)
    {
        $this->zohoOAuth = $zohoOAuth;
        $this->client = new Client([
            'base_uri' => 'https://www.zohoapis.eu/crm/v3/',
            'timeout' => 10.0,
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function getIndustryBodies(): array
    {
        return $this->getAccountsData($this->industryBodiesId);
    }

    /**
     * @throws GuzzleException
     */
    public function getAccountsData(string $cvid): array
    {
        $credentials = $this->zohoOAuth->credentials;
        $pageToken = null;
        $perPage = 200;

        $items = [];
        while (true) {
            $leadsResp = $this->client->get('Accounts', [
                'query' => [
                    'fields' => 'Website,User_Type,Record_Image,Account_Name,Account_Number,Owner,Account_Site,Annual_Revenue,Billing_City,Billing_Code,' .
                        'Billing_Country,Billing_State,Billing_Street,Created_By,Currency,Description,Email,Employees,Exchange_Rate,Fax,Industry,LinkedIn_Company,' .
                        'LinkedIn_Personal,Modified_By,Ownership,Parent_Account,Phone,Rating,Referral,Sales_Status,Shipping_City,Shipping_Code,Shipping_Country,Shipping_State,' .
                        'Shipping_Street,SIC_Code,Supplier_Coverage,Ticker_Symbol',

                    'cvid' => $cvid,

                    'per_page' => $perPage,
                    'page_token' => $pageToken,
                ],
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken ' . ($credentials ? $credentials->token : ''),
                ]
            ]);
            $response = json_decode($leadsResp->getBody()->getContents(), true);

            $pageToken = $response['info']['next_page_token'] ?? null;
            $data = $response['data'] ?? [];

            $items = array_merge($items, $data);

            if (!$pageToken || count($data) < $perPage) {
                break;
            }
        }

        return $items;
    }

    /**
     * @throws GuzzleException
     */
    public function getBuyingGroups(): array
    {
        return $this->getAccountsData($this->buyingGroupsId);
    }

    /**
     * @throws GuzzleException
     */
    public function getManufacturers(): array
    {
        return $this->getAccountsData($this->manufacturerDataViewId);
    }

    /**
     * @throws GuzzleException
     */
    public function getContractors(): array
    {
        return $this->getAccountsData($this->contractorDataViewId);
    }

    /**
     * @throws GuzzleException
     */
    public function getSuppliers(): array
    {
        return $this->getAccountsData($this->supplierDataViewId);
    }

    /**
     * @throws GuzzleException
     */
    public function getUsers(): array
    {
        $credentials = $this->zohoOAuth->credentials;
        $leadsResp = $this->client->get('users', [
            'query' => [
                'type' => 'AllUsers',
            ],
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . ($credentials ? $credentials->token : ''),
            ]
        ]);
        $response = json_decode($leadsResp->getBody()->getContents(), true);

        return $response['users'] ?? [];
    }

    public function getDeals() : array
    {
        $key = 'zoho.deals';
        $ttl = 60; // 60 seconds / 1 minute

        return Cache::remember($key, $ttl, function () {
            $credentials = $this->zohoOAuth->credentials;

            $page = 0;
            $perPage = 200;
            $offset = $perPage * $page;

            $items = [];
            $moreRecords = true;

            $query = "select
                    id,
                    Account_Name.Account_Name,
                    User_Type,
                    Contact_Name.Full_Name,
                    Contact_Name.Mobile,
                    Contact_Name.Email,
                    Contract_Sent,
                    Contract_Signed,
                    Description_1,
                    Stage
                from Deals
                where Stage = 'Passed to Rob'
                limit {$offset}, {$perPage}";

            while ($moreRecords) {
                $response = $this->client->post('coql', [
                    'body' => json_encode([
                        "select_query" => $query
                    ]),
                    'headers' => [
                        'Authorization' => 'Zoho-oauthtoken ' . ($credentials ? $credentials->token : ''),
                        //'Authorization' => 'Zoho-oauthtoken 1000.b5e1b3b1a53b0598c88628c632ee82a5.d0ec1652015711cab7e249d70bcc3ef0',
                    ]
                ]);

                $responseArr = json_decode($response->getBody()->getContents(), true);

                $moreRecords = (bool) $responseArr['info']['more_records'];
                $data = $responseArr['data'] ?? [];

                $items = array_merge($items, $data);

                $page++;
            }

            return $items;
        });
    }

    /**
     * @throws GuzzleException
     */
    public function updateDeal(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $credentials = $this->zohoOAuth->credentials;

        $response = $this->client->put("Deals/{$id}", [
            'body' => json_encode([
                "data" => [$data]
            ]),
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . ($credentials ? $credentials->token : ''),
                //'Authorization' => 'Zoho-oauthtoken 1000.b5e1b3b1a53b0598c88628c632ee82a5.d0ec1652015711cab7e249d70bcc3ef0',
            ]
        ]);

        Cache::forget('zoho.deals');

        return $response->getStatusCode() === 200;
    }

    /**
     * @throws GuzzleException
     */
    public function uploadFile(string $file): array|bool
    {
        $credentials = $this->zohoOAuth->credentials;

        //$fileContents = file_get_contents(storage_path('app') . '/' . $file);
        $fileStream = fopen(storage_path('app') . '/' . $file, 'r');

        if ($fileStream === false) {
            return false;
        }

        $response = $this->client->post("files", [
            'multipart' => [
                [
                    'name'     => 'file',
                    'contents' => $fileStream,
                ]
            ],
            'headers' => [
                //'Content-Type' => 'multipart/form-data',
                'Authorization' => 'Zoho-oauthtoken ' . ($credentials ? $credentials->token : ''),
                //'Authorization' => 'Zoho-oauthtoken 1000.af2a7533a03f98a304862043015631e8.019b0cbf8a8a7ad805fe0e1696b49a67',
            ]
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);

        //Log::debug('zohoCrmService->uploadFile response:', $responseData);

        if (empty($responseData['data'][0])) {
            return false;
        }

        return $responseData['data'][0];
    }

    public function searchAccounts(string $accountName): array
    {
        $credentials = $this->zohoOAuth->credentials;

        $response = $this->client->get("Accounts/search", [
            'query' => [
                'criteria' => "Account_Name:equals:{$accountName}",
                'fields'   => 'id,Account_Name,User_Type,Merchants_Received',
            ],
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . ($credentials ? $credentials->token : ''),
                //'Authorization' => 'Zoho-oauthtoken 1000.af2a7533a03f98a304862043015631e8.019b0cbf8a8a7ad805fe0e1696b49a67',
            ]
        ]);

        $accounts = json_decode($response->getBody()->getContents(), true);

        //Log::debug('zohoCrmService->searchAccounts response:', $accounts);

        if (empty($accounts['data'])) {
            return [];
        }

        return array_filter($accounts['data'], function ($account) use ($accountName) {
            return $account['Account_Name'] === $accountName;
        })[0];
    }

    public function updateAccount(string $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $credentials = $this->zohoOAuth->credentials;

        $response = $this->client->put("Accounts/{$id}", [
            'body' => json_encode([
                "data" => [$data]
            ]),
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . ($credentials ? $credentials->token : ''),
                //'Authorization' => 'Zoho-oauthtoken 1000.af2a7533a03f98a304862043015631e8.019b0cbf8a8a7ad805fe0e1696b49a67',
            ]
        ]);

        //Log::debug('zohoCrmService->updateAccount response:', json_decode($response->getBody()->getContents(), true));

        return $response->getStatusCode() === 200;
    }

    public function searchContacts(string $email): array
    {
        $credentials = $this->zohoOAuth->credentials;

        $response = $this->client->get("Contacts/search", [
            'query' => [
                'criteria' => "Email:equals:{$email}",
                'fields'   => 'id,Account_Name,User_Type,Email',
            ],
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . ($credentials ? $credentials->token : ''),
                //'Authorization' => 'Zoho-oauthtoken 1000.af2a7533a03f98a304862043015631e8.019b0cbf8a8a7ad805fe0e1696b49a67',
            ]
        ]);

        $contacts = json_decode($response->getBody()->getContents(), true);

        //Log::debug('zohoCrmService->searchContacts response:', $contacts);

        if (empty($contacts['data'])) {
            return [];
        }

        return array_filter($contacts['data'], function ($contact) use ($email) {
            return $contact['Email'] === $email;
        })[0];
    }

    public function updateContact(string $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $credentials = $this->zohoOAuth->credentials;

        $response = $this->client->put("Contacts/{$id}", [
            'body' => json_encode([
                "data" => [$data]
            ]),
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . ($credentials ? $credentials->token : ''),
                //'Authorization' => 'Zoho-oauthtoken 1000.af2a7533a03f98a304862043015631e8.019b0cbf8a8a7ad805fe0e1696b49a67',
            ]
        ]);

        //Log::debug('zohoCrmService->updateContact response:', json_decode($response->getBody()->getContents(), true));

        return $response->getStatusCode() === 200;
    }
}
