<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\VirtualExpo\SearchParamsDto;
use App\Dto\VirtualExpo\VirtualExpoDto;
use App\Exceptions\NotFoundException;
use App\Models\Role;
use App\Models\User;
use App\Models\VirtualExpo;
use App\Models\VirtualExpoVideo;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VirtualExpoRepository
{
    const int ITEMS_PER_PAGE = 10;
    const string DEFAULT_ORDER_FIELD_NAME = 'id';

    public function findVideos(SearchParamsDto $searchParamsDto, int $virtualExpoId, User $user): LengthAwarePaginator
    {
        $query = VirtualExpo::orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        $query->where([
            'id' => $virtualExpoId,
        ]);

        return $query->first()->videos()->paginate($itemsPerPage);
    }

    /**
     * @throws Exception
     */
    public function list(SearchParamsDto $searchParamsDto, User $user, bool $matchingProducts, bool $onlyGold): LengthAwarePaginator
    {
        $query = VirtualExpo::select([
            'virtual_expos.*'
        ])->orderBy(
            'virtual_expos.' . ($searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME),
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        )->join('users', 'users.id', '=', 'virtual_expos.user_id');

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        $roleIds = $user->roles->pluck('id')->toArray();

        $query->where([
            'is_active' => true,
        ])
            ->whereExists(function ($query) use ($roleIds) {
                $query->select(DB::raw(1))
                    ->from('virtual_expo_user_roles')
                    ->whereRaw('virtual_expo_id = virtual_expos.id')
                    ->whereIn('virtual_expo_user_roles.role_id', $roleIds);
            })
            ->whereRaw('`date_start` <= now() && `date_end` >= now()');

        if ($matchingProducts) {
            $ids = $user->getProductIdsAttribute()->toArray();
            if (!$ids) {
                throw new Exception('Not found', 404);
            }

            $query->whereRaw(
                '(select `product_id` from `product_user` where `user_id`=`virtual_expos`.`user_id` and `product_id` IN(' .
                join(",", $ids) . ') limit 1) is not null'
            );
        }


        if ($onlyGold) {
            $query->whereRaw('(select u.id from users u where u.id=users.billing_user_id and is_gold_account limit 1) is not null');
        }

        return $this->enrichResponse($query->paginate($itemsPerPage));
    }

    private function enrichResponse(LengthAwarePaginator $response): LengthAwarePaginator
    {
        $response->getCollection()->transform(function (VirtualExpo $item): array {
            if (count($item->videos) == 0) {
                return $item->toArray();
            }

            return array_merge($item->toArray(), [
                'videos' => $item->videos()->pluck('video'),
                'banner_image' => '/' . str_replace('public', 'storage', $item->getBannerImage()),
                'banner_image_left' => '/' . str_replace('public', 'storage', $item->getBannerImageLeft()),
            ]);
        });

        return $response;
    }

    public function find(SearchParamsDto $searchParamsDto, User $user, array $productIds): LengthAwarePaginator
    {
        $query = VirtualExpo::orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        if ($productIds) {
            $query->whereRaw(
                '(select `product_id` from `product_user` where `user_id`=`virtual_expos`.`user_id` and `product_id` IN(' .
                join(",", $productIds) . ') limit 1) is not null'
            );
        }

        if (!$user->isAdmin()) {
            if ($user->hasRole(ROle::ROLE_MANUFACTURER)) {
                $query->where(['user_id' => $user->getId()]);
            } else {
                $roleIds = $user->roles->pluck('id')->toArray();

                $query->where([
                    'is_active' => true,
                ])
                    ->whereExists(function ($query) use ($roleIds) {
                        $query->select(DB::raw(1))
                            ->from('virtual_expo_user_roles')
                            ->whereRaw('virtual_expo_id = virtual_expos.id')
                            ->whereIn('virtual_expo_user_roles.role_id', $roleIds);
                    })
                    ->whereRaw('`date_start` <= now() && `date_end` >= now()');
            }
        }

        return $this->enrichResponse($query->paginate($itemsPerPage));
    }

    public function getLastByManufacturerId(int $manufacturerId): ?VirtualExpo
    {
        $query = VirtualExpo::orderBy('id', 'desc')->where([
            'user_id' => $manufacturerId,
        ])->where(['is_active' => true]);

        return $query->first();
    }

    /**
     * @param int $id
     * @param VirtualExpoDto $dto
     * @return VirtualExpo|null
     * @throws NotFoundException
     * @throws Exception
     */
    public function update(int $id, VirtualExpoDto $dto): ?VirtualExpo
    {
        $item = $this->get($id);
        if ($item === null) {
            throw new NotFoundException();
        }

        $attachments = $dto->getAttachments();
        $attachmentsEpd = $dto->getAttachmentsEpd();
        $removeAttachments = $dto->getRemoveAttachments();

        $item->company_name = $dto->getName();
        $item->description = $dto->getDescription();
        $item->date_start = $dto->getDateStart()->format('Y-m-d H:i:s');
        $item->date_end = $dto->getDateEnd()->format('Y-m-d H:i:s');
        $item->is_active = $dto->getIsActive() ? 1 : 0;
        $item->product_categories = !empty($attachments);
        $item->epd_info = !empty($attachmentsEpd);

        if ($dto->getBgImage()) {
            $item->banner_image = $dto->getBgImage()->storeAs('public/' . $dto->getUserId() . '/virtual-expo', $dto->getBgImage()->getClientOriginalName());
        }

        if ($dto->getBgImageLeft()) {
            $item->banner_image_left = $dto->getBgImageLeft()->storeAs('public/' . $dto->getUserId() . '/virtual-expo', $dto->getBgImageLeft()->getClientOriginalName());
        }

        if (!empty($attachments)) {
            //$item->attachments()->where('description', '!=', 'epd')->delete();
            foreach ($attachments as $file) {
                $item->attach($file);
            }
        }

        if (!empty($attachmentsEpd)) {
            //$item->attachments()->where('description', '=', 'epd')->delete();
            foreach ($attachmentsEpd as $file) {
                $item->attach($file, ['description' => 'epd']);
            }
        }

        if (!empty($removeAttachments)) {
            $item->attachments()->whereIn('id', $removeAttachments)->delete();
        }

        $item->save();

        $item->roles()->sync($dto->getRoles());
        $item->videos()->delete();

        foreach (array_filter($dto->getVideos()) as $video) {
            $video = VirtualExpoVideo::create([
                'virtual_expo_id' => $item->getId(),
                'video' => $video,
                'user_id' => $dto->getUserId(),
            ]);

            $item->videos()->save($video);
        }

        return $item;
    }

    public function get(int $id): ?VirtualExpo
    {
        return VirtualExpo::where(['id' => $id])->first();
    }

    /**
     * @param int $id
     * @return bool
     * @throws NotFoundException
     */
    public function delete(int $id): bool
    {
        $virtualExpo = $this->get($id);
        if ($virtualExpo === null) {
            throw new NotFoundException();
        }

        $video = $virtualExpo->videos[0] ?? null;
        if ($video) {
            $video->delete();
        }

        return (bool)$virtualExpo->delete();
    }

    /**
     * @throws Exception
     */
    public function create(VirtualExpoDto $dto): ?VirtualExpo
    {
        $attachments = $dto->getAttachments();
        $attachmentsEpd = $dto->getAttachmentsEpd();

        /** @var VirtualExpo $item */
        if ($item = VirtualExpo::create([
            'company_name' => $dto->getName(),
            'date_start' => $dto->getDateStart()->format('Y-m-d H:i:s'),
            'date_end' => $dto->getDateEnd()->format('Y-m-d H:i:s'),
            'user_id' => $dto->getUserId(),
            'description'   => $dto->getDescription(),
            'date_start'   => $dto->getDateStart()->format('Y-m-d H:i:s'),
            'date_end'     => $dto->getDateEnd()->format('Y-m-d H:i:s'),
            'user_id'      => $dto->getUserId(),
            'banner_image_left' => $dto->getBgImageLeft() ? $dto->getBgImageLeft()->storeAs('public/' . $dto->getUserId() . '/virtual-expo', $dto->getBgImageLeft()->getClientOriginalName()) : '',
            'banner_image' => $dto->getBgImage() ? $dto->getBgImage()->storeAs('public/' . $dto->getUserId() . '/virtual-expo', $dto->getBgImage()->getClientOriginalName()) : '',
            'expo_live' => $dto->getIsActive(),
            'product_categories' => !empty($attachments),
            'tech_support' => true,
            'pim_uploaded' => false,
            'epd_info' => !empty($attachmentsEpd)
        ])) {
            $item->roles()->sync($dto->getRoles());

            if (!empty($attachments)) {
                foreach ($attachments as $file) {
                    $item->attach($file);
                }
            }

            if (!empty($attachmentsEpd)) {
                foreach ($attachmentsEpd as $file) {
                    $item->attach($file, ['description' => 'epd']);
                }
            }

            foreach (array_filter($dto->getVideos()) as $video) {
                $video = VirtualExpoVideo::create([
                    'virtual_expo_id' => $item->getId(),
                    'video' => $video,
                    'user_id' => $dto->getUserId(),
                ]);

                $item->videos()->save($video);
            }

            return $item;
        }

        return null;
    }
}
