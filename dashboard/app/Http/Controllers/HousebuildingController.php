<?php

namespace App\Http\Controllers;

use App\Models\HousebuildingBudget;
use App\Models\HousebuildingProduct;
use App\Service\HousebuildingService;
use App\Service\UserService;
use App\Dto\SearchParamsDto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class HousebuildingController
{
    private UserService $userService;
    private HousebuildingService $housebuildingService;

    public function __construct(UserService $userService, HousebuildingService $housebuildingService)
    {
        $this->userService = $userService;
        $this->housebuildingService = $housebuildingService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $searchParamsDto = SearchParamsDto::createFromRequest($request);

        $houseNames = $this->housebuildingService->getHouseNames();
        $products = $this->housebuildingService->getProducts();
        $rows = [];
        $grouped = [];

        // group by product name
        foreach($products as $product) {
            $grouped[$product['product_name']][] = $product;
        }

        foreach($grouped as $productName => $group) {
            $row = [
                'category'          => $group[0]['category'],
                'product_name'      => $group[0]['product_name'],
                'product_ref'       => $group[0]['product_ref'],
                'uom'               => $group[0]['uom']
            ];

            foreach($group as $item) {
                $row[$item['house_name']] = $item['quantity'];
            }

            $rows[] = $row;
        }

        $orderBy = $searchParamsDto->getOrderBy();
        $sortDesc = $searchParamsDto->getOrderSortDesc();
        $search = $searchParamsDto->getSearch();

        // apply sorting
        if (!empty($orderBy)) {
            usort($rows, function ($a, $b) use ($orderBy, $sortDesc) {
                if (empty($a[$orderBy]) || empty($b[$orderBy])) {
                    return 0;
                }

                if ($sortDesc) {
                    if (is_numeric($a[$orderBy]) && is_numeric($b[$orderBy])) {
                        return $b[$orderBy] > $a[$orderBy];
                    }

                    return strcmp($b[$orderBy], $a[$orderBy]);
                }

                if (is_numeric($a[$orderBy]) && is_numeric($b[$orderBy])) {
                    return $b[$orderBy] > $a[$orderBy];
                }

                return strcmp($b[$orderBy], $a[$orderBy]);
            });
        }

        // apply search
        $rows = array_values(array_filter($rows, function($row) use($search) {
            foreach($row as $value) {
                if (str_contains((string)$value, $search)) {
                    return true;
                }
            }

            return false;
        }));

        $headers = collect(['headers' => $houseNames]);
        $paginator = $this->paginate($rows, $searchParamsDto->getItemsPerPage(), $searchParamsDto->getPage());

        return new JsonResponse($headers->merge($paginator));
    }

    public function paginate($items, $perPage = 10, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    public function getHouseNames(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $options = $this->housebuildingService->getHouseNames();

        return new JsonResponse($options);
    }

    public function getCategories(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $options = $this->housebuildingService->getCategories();

        return new JsonResponse($options);
    }

    public function getTotals(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        //$category = $request->get('category') === 'null' ? null : $request->get('category');

        $query = HousebuildingBudget::query()->select('*');

//        if (is_null($category)) {
//            $query->whereNull('category_name');
//        } else {
//            $query->where('category_name', '=', $category);
//        }

        $budgets = $query->get()->toArray();
        $categoryGroups = [];

        foreach($budgets as $budget) {
            $categoryGroups[$budget['category_name']][] = $budget;
        }

        foreach($categoryGroups as $category => $budgets) {
            $budgetGroups = [];

            foreach($budgets as $budget) {
                $budgetGroups[$budget['type_str']][] = $budget['budget'];
            }

            $categoryGroups[$category] = $budgetGroups;
        }

        return new JsonResponse($categoryGroups);
    }

    public function updateBudget(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $category = $request->get('category');
        $houseName = $request->get('house_name');
        $budgetVal = $request->get('budget') ?? 0;

        if (empty($category) || empty($houseName)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $query = HousebuildingBudget::query()->where([
            ['house_name', '=', $houseName],
            ['category_name', '=', $category],
            ['type', '=', 0]
        ]);

        $budget = $query->get()->first();
        $budget->setBudget($budgetVal);
        $budget->save();

        return new JsonResponse($budget);
    }
}
