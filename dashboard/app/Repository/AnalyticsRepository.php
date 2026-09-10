<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\Analytics\EventDto;
use App\Models\AnalyticsEvent;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;

class AnalyticsRepository
{
    public function newEvent(EventDto $dto): AnalyticsEvent
    {
        $event = new AnalyticsEvent();
        $event->location = $dto->getLocation();
        $event->action = $dto->getAction();
        $event->object_id = $dto->getObjectId();
        $event->object_type = $dto->getObjectType();
        $event->user_id = $dto->getUserId();
        $event->url = $dto->getUrl();
        $event->target = $dto->getTarget();
        $event->save();

        return $event;
    }

    public function getTotalBannerImpressionsForExpoId($expoId, ?Carbon $dateFrom, ?Carbon $dateTo): int
    {
        $query = AnalyticsEvent::where([
            'action' => 'view',
            'object_type' => 'virtual-expo',
            'object_id' => $expoId,
        ]);

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        return $query->count();
    }

    public function getTotalDocumentDownloadsExpoId($expoId, ?Carbon $dateFrom, ?Carbon $dateTo): int
    {
        $query = AnalyticsEvent::where([
            'action' => 'download',
            'object_type' => 'virtual-expo',
            'object_id' => $expoId,
        ]);

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        return $query->count();
    }

    public function getTotalBannerClicksForExpoId($expoId, ?Carbon $dateFrom, ?Carbon $dateTo): int
    {
        $query = AnalyticsEvent::where([
            'action' => 'click',
            'object_type' => 'virtual-expo',
            'object_id' => $expoId,
        ]);

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        return $query->count();
    }

    public function getTotalVideoViewsForExpoId($expoId, ?Carbon $dateFrom, ?Carbon $dateTo): int
    {
        $query = AnalyticsEvent::where([
            'action' => 'play-video',
            'object_type' => 'virtual-expo',
            'object_id' => $expoId,
        ]);

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        return $query->count();
    }

    public function getTotalBannerImpressionsByLocationForExpoId($expoId, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $query = AnalyticsEvent::where([
            'action' => 'view',
            'object_type' => 'virtual-expo',
            'object_id' => $expoId,
        ]);

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }
        $query->select(['url', 'location']);
        $query->selectRaw('count(*) qty');

        $items = $query->groupBy(['url', 'location'])->get();

        $result = [];
        foreach ($items as $item) {
            $result[$item->url . ' ' . $item->location] = $item->qty;
        }

        return $result;
    }

    public function getTotalBannerClicksByLocationForExpoId($expoId, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $query = AnalyticsEvent::where([
            'action' => 'click',
            'object_type' => 'virtual-expo',
            'object_id' => $expoId,
        ]);

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }
        $query->select(['url', 'location']);
        $query->selectRaw('count(*) qty');

        $items = $query->groupBy(['url', 'location'])->get();

        $result = [];
        foreach ($items as $item) {
            $result[$item->url . ' ' . $item->location] = $item->qty;
        }

        return $result;
    }

    public function getTotalBannerImpressionsByUserTypeForExpoId($expoId, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $query = AnalyticsEvent::where([
            'action' => 'view',
            'object_type' => 'virtual-expo',
            'object_id' => $expoId,
        ]);

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }
        $query->select(['user_id']);
        $query->selectRaw('count(*) qty');

        $items = $query->groupBy(['user_id'])->get();

        $result = ['contractor' => 0, 'merchant' => 0];
        foreach ($items as $item) {
            /** @var User $user */
            $user = User::where(['id' => $item->user_id])->first();
            if (!$user) {
                continue;
            }
            if ($user->hasRole(Role::ROLE_CONTRACTOR)) {
                $result['contractor'] += $item->qty;
            }
            if ($user->hasRole(Role::ROLE_COMPANY_SLUG)) {
                $result['merchant'] += $item->qty;
            }
        }

        return $result;
    }

    public function getTotalBannerClicksByUserTypeForExpoId($expoId, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $query = AnalyticsEvent::where([
            'action' => 'click',
            'object_type' => 'virtual-expo',
            'object_id' => $expoId,
        ]);

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }
        $query->select(['user_id']);
        $query->selectRaw('count(*) qty');

        $items = $query->groupBy(['user_id'])->get();

        $result = ['contractor' => 0, 'merchant' => 0];
        foreach ($items as $item) {
            /** @var User $user */
            $user = User::where(['id' => $item->user_id])->first();
            if (!$user) {
                continue;
            }
            if ($user->hasRole(Role::ROLE_CONTRACTOR)) {
                $result['contractor'] += $item->qty;
            }
            if ($user->hasRole(Role::ROLE_COMPANY_SLUG)) {
                $result['merchant'] += $item->qty;
            }
        }

        return $result;
    }
}
