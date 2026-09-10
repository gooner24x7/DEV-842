<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\SupplyChainUser\SearchParamsDto;
use App\Dto\SupplyChainUser\SupplyChainUserDto;
use App\Models\Certificate;
use App\Models\Questionnaire\Project;
use App\Models\SupplyChainUser;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplyChainUserRepository
{
    const string DEFAULT_ORDER_FIELD_NAME = 'id';
    const int ITEMS_PER_PAGE = 20;

    public function find(SearchParamsDto $searchParamsDto, User $user): LengthAwarePaginator
    {
        $query = SupplyChainUser::query()->select('supply_chain_users.*')
            ->leftJoin('roles', 'roles.id', '=', 'supply_chain_users.role_id')
            ->where('supply_chain_users.created_by', '=', $user->getId());

        if ($searchParamsDto->getSearch()) {
            $query->where(function ($query) use ($searchParamsDto) {
                $query->orWhere('supply_chain_users.first_name', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('supply_chain_users.last_name', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('supply_chain_users.business_name', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('supply_chain_users.email', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('supply_chain_users.phone', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('supply_chain_users.addr_line_1', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('supply_chain_users.addr_line_2', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('supply_chain_users.city', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('supply_chain_users.country', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('supply_chain_users.postcode', 'like', '%' . $searchParamsDto->getSearch() . '%');
            });
        }

        if (!empty($searchParamsDto->getCompanyNames())) {
            $query->whereIn('supply_chain_users.business_name', $searchParamsDto->getCompanyNames());
        }

        $orderBy = $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME;
        $orderBy = 'supply_chain_users.' . $orderBy;
        $orderBy = ($orderBy == 'role_name') ? 'roles.name' : $orderBy;

        $query = $query->orderBy(
            $orderBy,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }

    public function store(SupplyChainUserDto $dto, User $user): SupplyChainUser
    {
        return SupplyChainUser::create([
            'role_id' => $dto->getRoleId(),
            'first_name' => $dto->getFirstName(),
            'last_name' => $dto->getLastName(),
            'business_name' => $dto->getBusinessName(),
            'email' => $dto->getEmail(),
            'phone' => $dto->getPhone(),
            'addr_line_1' => $dto->getAddressLine1(),
            'addr_line_2' => $dto->getAddressLine2(),
            'city' => $dto->getCity(),
            'country' => $dto->getCountry(),
            'postcode' => $dto->getPostcode(),
            'created_by' => $user->getId(),
        ]);
    }

    public function update(SupplyChainUser $user, SupplyChainUserDto $dto): SupplyChainUser
    {
        $user->setRoleId($dto->getRoleId());
        $user->setFirstName($dto->getFirstName());
        $user->setLastName($dto->getLastName());
        $user->setBusinessName($dto->getBusinessName());
        $user->setEmail($dto->getEmail());
        $user->setPhone($dto->getPhone());
        $user->setAddressLine1($dto->getAddressLine1());
        $user->setAddressLine2($dto->getAddressLine2());
        $user->setCity($dto->getCity());
        $user->setCountry($dto->getCountry());
        $user->setPostcode($dto->getPostcode());

        $user->save();

        return $user;
    }

    public function delete(int $userId): bool
    {
        $user = $this->getUserById($userId);

        return $user->delete();
    }

    public function getUserById(int $userId): SupplyChainUser
    {
        return SupplyChainUser::where(['id' => $userId])->first();
    }

    public function getCompanyNames(User $user): array
    {
        $query = SupplyChainUser::query()->select('business_name')->distinct()->where(['created_by' => $user->getId()]);

        return $query->pluck('business_name')->toArray();
    }

    public function getSubcontractors(User $user, \App\Dto\User\SearchParamsDto $searchParamsDto): Collection
    {
        $billingUserId = $user->getBillingUserId() ?? $user->getId();

        $projectLat = null;
        $projectLong = null;

        if (!empty($searchParamsDto->getProject())) {
            $project = Project::where('name', '=', $searchParamsDto->getProject())->first();
            $projectLat = $project->getLat();
            $projectLong = $project->getLong();
        }

        $query = DB::table('users')
            ->select([
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.postcode',
                'user_creditsafe.risk_score',
            ])
            ->selectRaw('ROUND(users.contractor_score, 0) as contractor_score')
            ->selectRaw('(SELECT GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR \',\') FROM products p INNER JOIN product_user pu ON pu.product_id = p.id WHERE pu.user_id = users.id) as products');

        if (!empty($projectLat) && !empty($projectLong)) {
            $query->selectRaw(
                '(0.62137 * (6371 * acos(cos(radians(?))
                * cos(radians(`users`.`lat`))
                * cos(radians(`users`.`long`)
                - radians(?))
                + sin(radians(?))
                * sin(radians(`users`.`lat`))))) as distance',
                [$projectLat, $projectLong, $projectLat]
            );
        } else {
            $query->selectRaw('0 as distance');
        }

        $query->join('users_preferred_subcontractors', 'users_preferred_subcontractors.subcontractor_id', '=', 'users.id')
            ->leftJoin('user_creditsafe', 'user_creditsafe.user_id', '=', 'users.id')
            ->where('users_preferred_subcontractors.user_id', '=', $billingUserId);

        $certTypes = array_column(Certificate::TYPES, 'slug');

        foreach($certTypes as $ct) {
            $query->selectRaw("(SELECT (count(*)>0) FROM certificates WHERE certificates.user_id = users.id AND certificates.type = '{$ct}') as `{$ct}`");
        }

        $search = $searchParamsDto->getSearch();

        if (!empty($search)) {
            $query->where(function($query) use ($search) {
                $query->where('users.first_name', 'like', '%' . $search . '%')
                    ->orWhere('users.postcode', 'like', '%' . $search . '%');
            });
        }

        if (!empty($searchParamsDto->getRadius())) {
            $query->havingRaw('distance < ?', [$searchParamsDto->getRadius()]);
        }

        //dd($query->toSql());

        $results = $query->get();

        if (!empty($searchParamsDto->getCertType()) && $searchParamsDto->getCertStatus() !== null) {
            $results = $results->filter(function($item) use ($searchParamsDto) {
                return $item->{$searchParamsDto->getCertType()} === $searchParamsDto->getCertStatus();
            })->values();
        }

        $selectedProducts = $searchParamsDto->getProducts();

        if (!empty($selectedProducts)) {
            $results = $results->filter(function($item) use ($selectedProducts) {
                $products = explode(',', $item->products);
                foreach($products as $product) {
                    if (in_array($product, $selectedProducts)) {
                        return true;
                    }
                }
                return false;
            })->values();
        }

        return $results;
    }
}
