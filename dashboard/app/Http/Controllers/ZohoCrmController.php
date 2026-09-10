<?php

namespace App\Http\Controllers;

use App\Dto\SearchParamsDto;
use App\Service\ZohoReportService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Redis;

class ZohoCrmController extends Controller
{
    private ZohoReportService $service;

    public function __construct(ZohoReportService $service)
    {
        $this->service = $service;
    }

    public function getDefaultValues(Request $request)
    {
        return $this->service->getSettings();
    }

    public function index(Request $request, Redis $redis): void
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="report' . date('YmdHis') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $planToLaunch = $request->get("planToLaunch");
        $allocateToStaff = $request->get('allocateToStaff');

        $this->service->outputReport($planToLaunch, $allocateToStaff);
    }

    public function getDeals(Request $request): JsonResponse
    {
        $searchParams = SearchParamsDto::createFromRequest($request);

        $perPage = $searchParams->getItemsPerPage();
        $page = $searchParams->getPage();
        $search = $searchParams->getSearch();
        $orderBy = $searchParams->getOrderBy();
        $sortDesc = $searchParams->getOrderSortDesc();

        // get all deals
        $deals = $this->service->getDeals();

        if (empty($deals)) {
            return new JsonResponse(['data' => [], 'total' => 0]);
        }

        // apply search filter
        if (!empty($search)) {
            $deals = array_values(array_filter($deals, function ($deal) use ($search) {
                return str_contains(strtolower($deal['Account_Name.Account_Name']), strtolower($search));
            }));
        }

        // record the total
        $total = count($deals);

        // apply sorting
        if (!empty($orderBy)) {
            usort($deals, function ($a, $b) use ($orderBy, $sortDesc) {
                if ($sortDesc) {
                    return strcmp($b[$orderBy], $a[$orderBy]);
                }

                return strcmp($a[$orderBy], $b[$orderBy]);
            });
        }

        // apply pagination
        if ($perPage != '-1' && $total > $perPage) {
            $offset = $perPage * ($page - 1);
            $deals = array_slice($deals, $offset, $perPage);
        }

        return new JsonResponse(['data' => $deals, 'total' => $total]);
    }

    public function updateDeal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required',
            'Description_1' => 'required',
        ]);

        try {
            $update = $this->service->updateDeal($data['id'], $data);
        } catch (GuzzleException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

        return new JsonResponse($update);
    }
}
