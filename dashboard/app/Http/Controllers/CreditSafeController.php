<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Service\CreditSafeService;
use Illuminate\Http\JsonResponse;

class CreditSafeController
{
    private CreditSafeService $creditSafeService;

    public function __construct(CreditSafeService $creditSafeService)
    {
        $this->creditSafeService = $creditSafeService;
    }
}
