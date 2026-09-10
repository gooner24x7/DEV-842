<?php
declare(strict_types=1);

namespace App\Dto\SupplyFitEnquiry;

use App\Models\User;
use Illuminate\Http\Request;

class SupplyFitEnquiryBatchDto
{
    /** @var SupplyFitEnquiryDto[] */
    private array $items = [];
    private ?User $user;

    public function __construct(array $items, User $user)
    {
        $this->items = $items;
        $this->user = $user;
    }

    public static function createFromRequest(Request $request, User $user): self
    {
        $data = $request->validate([
            'items' => 'present|array',
            'items.*.postcode' => 'required',
            'items.*.product_id' => 'required',
            'items.*.days' => 'required',
            'items.*.assumed_end_date' => '',
            'items.*.comment' => '',
            'items.*.type' => '',
            'items.*.scope' => '',
            'items.*.attachment' => '',
            'project_id' => '',
            'works_package_id' => '',
            'status' => ''
        ]);

        $items = array_map(static function (array $item) use ($user, $data): SupplyFitEnquiryDto {
            $item['project_id'] = $data['project_id'] ?? null;
            $item['works_package_id'] = $data['works_package_id'] ?? 0;
            $item['status'] = (int) $data['status'];

            return SupplyFitEnquiryDto::createFromArray($item, $user->getId());
        }, $data['items'] ?? []);

        return new self($items, $user);
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}
