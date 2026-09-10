<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\User\SearchParamsDto;
use App\Models\Certificate;
use App\Models\PreferredSubcontractor;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CertificateRepository
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function get(mixed $userIds): array
    {
        $query = Certificate::query()->select('*')
            ->whereIn('user_id', $userIds);

        $certificates = $query->get();

        $grouped = $certificates->groupBy('user_id');

        $grouped = $grouped->transform(function($item) {
            return $item->groupBy('type');
        });

        return $grouped->toArray();
    }

    public function getTypes(): array
    {
        return Certificate::TYPES;
    }

    public function getStats(User $user): array
    {
        $preferredSubcontractorIds = $this->userRepository->getPreferredSubcontractors($user)->pluck('id')->toArray();
        $total = count($preferredSubcontractorIds);

        $certTypes = $this->getTypes();

        // todo replace this with a single query
        foreach ($certTypes as $key => $data) {
            $data['stats'] = [0, 0];

            $subcontractors = $this->getSubcontractorCertData($preferredSubcontractorIds, $data['slug']);

            foreach($subcontractors as $subcontractor) {
                if ($subcontractor->status === 1) {
                    $data['stats'][0]++;
                } else {
                    $data['stats'][1]++;
                }
            }

            $data['stats'][0] = ($total > 0) ? number_format((($data['stats'][0] / $total) * 100)) : 0;
            $data['stats'][1] = ($total > 0) ? number_format((($data['stats'][1] / $total) * 100)) : 0;

            $certTypes[$key] = $data;
        }

        return [
            'total' => $total,
            'data' => $certTypes,
        ];
    }

    private function getSubcontractorCertData(array $prefSubcontractorIds, string $type): Collection
    {
        $query = DB::table('users')->select('users.id')
            ->selectRaw('(SELECT COUNT(*) FROM certificates WHERE certificates.user_id = users.id AND certificates.type = ?) as status', [$type])
            ->whereIn('users.id', $prefSubcontractorIds);

        return $query->get();
    }

    public function getDashboardStats(User $user): array
    {
        $data = [
            'total_subcontractors' => 0,
            'total_compliant' => 0
        ];

        $billingUserId = $user->getBillingUserId() ?? $user->getId();

        $query = DB::table('users')->select('users.id', 'users.first_name', 'users.last_name')
            ->join('users_preferred_subcontractors', 'users_preferred_subcontractors.subcontractor_id', '=', 'users.id')
            ->where('users_preferred_subcontractors.user_id', '=', $billingUserId);

        $subcontractors = $query->get();
        $subcontractorIds = $subcontractors->pluck('id');

        $query = DB::table('certificates')
            ->selectRaw('COUNT(*) as total, user_id')
            ->whereIn('user_id', $subcontractorIds)
            ->groupBy('user_id');

        $subcontractorStats = $query->get();
        $total = count($this->getTypes());

        foreach ($subcontractors as $subcontractor) {
            $data['total_subcontractors']++;

            foreach ($subcontractorStats as $stat) {
                if ($stat->user_id === $subcontractor->id && $stat->total >= $total) {
                    $data['total_compliant']++;
                }
            }
        }

        return $data;
    }
}
