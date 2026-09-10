<?php
declare(strict_types=1);

namespace App\Repository;

use App\DataProvider\UserDataProvider;
use App\Dto\VirtualExpo\SharedContactSearchParamsDto;
use App\Dto\VirtualExpo\VirtualExpoSharedContactDto;
use App\Exceptions\NotFoundException;
use App\Models\Role;
use App\Models\User;
use App\Models\VirtualExpo;
use App\Models\VirtualExpoSharedContact;
use App\Models\VirtualExpoVideo;
use Illuminate\Pagination\LengthAwarePaginator;

class VirtualExpoSharedContactRepository
{
    const int ITEMS_PER_PAGE = 10;
    const string DEFAULT_ORDER_FIELD_NAME = 'id';

    private UserDataProvider $userDataProvider;

    public function __construct(
        UserDataProvider $userDataProvider
    )
    {
        $this->userDataProvider = $userDataProvider;
    }

    public function find(
        SharedContactSearchParamsDto $searchParamsDto,
        User                         $user
    ): LengthAwarePaginator
    {
        $query = VirtualExpoSharedContact::orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        if ($user->hasRole(ROle::ROLE_MANUFACTURER)) {
            if ($searchParamsDto->getId()) {
                $query->where([
                    'virtual_expo_id' => $searchParamsDto->getId(),
                ]);
            }
        } elseif (!$user->hasRole(Role::ROLE_ADMIN_SLUG)) {
            $query->where(['user_id' => $user->getId()]);
        }

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $this->enrichResponse($query->paginate($itemsPerPage));
    }

    private function enrichResponse(LengthAwarePaginator $response): LengthAwarePaginator
    {
        $response->getCollection()->transform(function (VirtualExpoSharedContact $item): array {
            /** @var VirtualExpo $virtualExpo */
            $virtualExpo = VirtualExpo::find(['id' => $item->getVirtualExpoId()])->first();
            if (!$virtualExpo) {
                return [];
            }

            /** @var VirtualExpoVideo $video */
            $video = $virtualExpo->videos[0];
            if (!$video) {
                return $virtualExpo->toArray();
            }

            $user = $this->userDataProvider->getById($item->getUserId());

            return array_merge($virtualExpo->toArray(), [
                'id' => $item->getId(),
                'video' => $video->getVideo(),
                'banner_image' => '/' . str_replace('public', 'storage', $virtualExpo->getBannerImage()),
                'username' => ($user) ? $user->getUsername() : '',
                'created_at' => $item->getCreatedAt(),
            ]);
        });

        return $response;
    }

    /**
     * @param int $id
     * @return bool
     * @throws NotFoundException
     */
    public function delete(int $id): bool
    {
        $virtualExpoSharedContact = $this->get($id);
        if ($virtualExpoSharedContact === null) {
            throw new NotFoundException();
        }

        return (bool)$virtualExpoSharedContact->delete();
    }

    public function get(int $id): ?VirtualExpoSharedContact
    {
        return VirtualExpoSharedContact::where(['id' => $id])->first();
    }

    public function create(VirtualExpoSharedContactDto $dto): ?VirtualExpoSharedContact
    {
        /** @var VirtualExpoSharedContact $item */
        if ($item = VirtualExpoSharedContact::create([
            'virtual_expo_id' => $dto->getVirtualExpoId(),
            'user_id' => $dto->getUserId(),
            'virtual_expo_user_id' => 0,
        ])) {
            return $item;
        }

        return null;
    }

    public function findByUserExpoId(int $userId, int $virtualExpoId): ?VirtualExpoSharedContact
    {
        return VirtualExpoSharedContact::where(['user_id' => $userId, 'virtual_expo_id' => $virtualExpoId])->first();
    }
}
