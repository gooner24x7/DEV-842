<?php
declare(strict_types=1);

namespace App\Dto\Question;

use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Http\Request;

class QuestionDto
{
    private int $worksPackageId;
    private int $projectId;
    private string $postcode;
    private int $productId;
    private string $days;
    private string $comment;
    private int $userId;
    private array $manufacturerProductSelected = [];
    private bool $sendToNational;

    /** @var UploadedFile[] */
    private array $attachment;
    private string $type;
    private int $status;
    private int $scope;

    public function __construct(
        int    $userId,
        string $days,
        int    $productId,
        string $postcode,
        int    $worksPackageId,
        int    $projectId,
        string $comment,
        string $type,
        array  $attachment,
        array  $manufacturerProductSelected,
        bool   $sendToNational,
        int    $status,
        int    $scope,
    ) {
        $this->userId = $userId;
        $this->days = $days;
        $this->productId = $productId;
        $this->postcode = $postcode;
        $this->worksPackageId = $worksPackageId;
        $this->projectId = $projectId;
        $this->comment = $comment;
        $this->attachment = $attachment;
        $this->type = $type;
        $this->manufacturerProductSelected = $manufacturerProductSelected;
        $this->sendToNational = $sendToNational;
        $this->status = $status;
        $this->scope = $scope;
    }

    public static function createFromArray(array $data, int $userId): QuestionDto
    {
        return new QuestionDto(
            $userId,
            (string)($data['days'] ?? ''),
            (int)($data['product_id'] ?? 0),
            ($data['postcode'] ?? ''),
            (int)($data['works_package_id'] ?? 0),
            (int)($data['project_id'] ?? 0),
            ($data['comment'] ?? ''),
            ($data['type'] ?? ''),
            ($data['attachment'] ?? []),
            (isset($data['manufacturer_product_selected']) ? array_map(function ($item) {
                $item['qty'] = (isset($item['qty'])) ? (int)$item['qty'] : 0;
                return $item;
            }, $data['manufacturer_product_selected']) : []),
            (bool)($data['send_to_national'] ?? false),
            (int)($data['status'] ?? 0),
            (int)($data['scope'] ?? 0),
        );
    }

    public static function createFromRequest(Request $request, int $userId): QuestionDto
    {
        $data = $request->validate([
            'postcode' => 'required',
            'product_id' => '',
            'manufacturer_product_selected' => '',
            'days' => 'required',
            'comment' => '',
            'works_package_id' => '',
            'project_id' => '',
            'type' => 'required',
            'attachment' => '',
            'send_to_national' => '',
            'status' => '',
            'scope' => '',
        ]);

        return new self(
            $userId,
            (string)($data['days'] ?? ''),
            (int)($data['product_id'] ?? 0),
            ($data['postcode'] ?? ''),
            (int)($data['works_package_id'] ?? 0),
            (int)($data['project_id'] ?? 0),
            ($data['comment'] ?? ''),
            ($data['type'] ?? ''),
            ($data['attachment'] ?? []),
            ($data['manufacturer_product_selected'] ?? []),
            !empty($data['send_to_national']),
            (int)($data['status'] ?? 0),
            (int)($data['scope'] ?? 0),
        );
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getDays(): string
    {
        return $this->days;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getPostcode(): string
    {
        return $this->postcode;
    }

    public function getWorksPackageId(): int
    {
        return $this->worksPackageId;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getAttachment(): array
    {
        return $this->attachment;
    }

    public function getManufacturerProductSelected(): array
    {
        return $this->manufacturerProductSelected;
    }

    public function getSendToNational(): bool
    {
        return $this->sendToNational;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getScope(): int
    {
        return $this->scope;
    }

    public function setProductId(int $productId): self
    {
        $this->productId = $productId;

        return $this;
    }
}
