<?php
declare(strict_types=1);

namespace App\Repository;

use App\Models\InquiryMerchant;
use App\Models\User;
use Carbon\Carbon;

class InquiryMerchantRepository
{
    public function setCalled(int $userId, string $comment, User $user, int $qid = 0, string $it = 'purchase_hire'): InquiryMerchant
    {
        return InquiryMerchant::create([
            'inquiry_id' => $qid,
            'user_id' => $userId,
            'inquiry_type' => ($qid) ? $it : '',
            'author_id' => $user->id,
            'called_at' => Carbon::now(),
            'comment' => $comment
        ]);
    }

    public function getRecord(int $userId, $inquiryId = 0): InquiryMerchant|null
    {
        return InquiryMerchant::select('called_at', 'comment')
            ->where(['user_id' => $userId, 'inquiry_id' => $inquiryId])
            ->orderBy('id', 'desc')
            ->first();
    }
}
