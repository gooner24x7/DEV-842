<?php

declare(strict_types=1);

namespace App\Dto\Reports;

class TotalEnquiryItemDto
{
    private string $month;
    private int $total;

    public static function createFromArray(array $data): self
    {
        $item = new TotalEnquiryItemDto();
        $item->month = $data['month'] ?? '';
        $item->total = $data['total'] ?? 0;

        return $item;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getMonth(): string
    {
        return $this->month;
    }
}
