<?php
declare(strict_types=1);

namespace App\Dto\Question;

use App\Models\User;
use Illuminate\Http\Request;

class QuestionBatchDto
{
    /** @var QuestionDto[] */
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
            'items.*.manufacturer_product_id' => '',
            'items.*.days' => 'required',
            'items.*.comment' => '',
            'items.*.attachment' => '',
            'items.*.type' => 'required',
            'items.*.send_to_national' => '',
            'items.*.scope' => '',
            'works_package_id' => '',
            'project_id' => '',
            'status' => ''
        ]);

        $items = array_map(static function (array $item) use ($user, $data): QuestionDto {
            $item['works_package_id'] = $data['works_package_id'] ?? null;
            $item['project_id'] = $data['project_id'] ?? null;
            $item['status'] = (int) $data['status'];

            return QuestionDto::createFromArray($item, $user->getId());
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
