<?php
declare(strict_types=1);

namespace App\Dto\VirtualExpo;

use App\Models\User;
use Illuminate\Http\Request;

class VirtualExpoSharedContactDto
{
    private int $virtualExpoId;
    private int $userId;

    public function __construct(int $virtual_expo_id, User $user)
    {
        $this->virtualExpoId = $virtual_expo_id;
        $this->userId = $user->getId();
    }

    public static function createFromRequest(Request $request, User $user): self
    {
        $data = $request->validate([
            'virtual_expo_id' => 'required',
        ]);

        return new self(
            $data['virtual_expo_id'] ?? 0,
            $user
        );
    }

    public function getVirtualExpoId(): int
    {
        return $this->virtualExpoId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
