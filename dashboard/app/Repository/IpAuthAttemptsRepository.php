<?php
declare(strict_types=1);

namespace App\Repository;

use App\Models\IpAuthAttempt;

class IpAuthAttemptsRepository
{
    public function create($ip): ?IpAuthAttempt
    {
        if ($item = IpAuthAttempt::create([
            'ip' => $ip,
            'attempts' => 1,
        ])) {
            return $item;
        }

        return null;
    }

    public function getList()
    {
        return IpAuthAttempt::get();
    }

    public function getByIp(string $ip): ?IpAuthAttempt
    {
        return IpAuthAttempt::where(['ip' => $ip])->first();
    }

    public function incrementAttempts(IpAuthAttempt $item): IpAuthAttempt
    {
        $item = $this->getById($item->id);

        $item->attempts = $item->attempts + 1;
        $item->save();

        return $item;
    }

    public function getById(int $id): ?IpAuthAttempt
    {
        return IpAuthAttempt::where(['id' => $id])->first();
    }

}
