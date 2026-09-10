<?php
declare(strict_types=1);

namespace App\Dto\SupplyFitEnquiry;

use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Http\Request;

class SupplyFitEnquiryDto
{
    private ?int $projectId;
    private array $worksPackages;
    private string $postcode;
    private ?int $productId;
    private string $days;
    private ?string $actual_starting_date;
    private ?string $assumed_end_date;
    private ?string $actual_end_date;
    private string $comment;
    private int $userId;
    private string $type;
    private int $status;
    private int $scope;

    /** @var UploadedFile[] */
    private array $attachment;
    private array $project_attachment;

    public function __construct(
        int     $userId,
        string  $days,
        ?string $actual_starting_date,
        ?string $assumed_end_date,
        ?string $actual_end_date,
        ?int    $productId,
        string  $postcode,
        ?int    $projectId,
        array    $worksPackages,
        string  $comment,
        string  $type,
        array   $attachment,
        array   $project_attachment,
        int     $status,
        int     $scope,
    ) {
        $this->userId = $userId;
        $this->days = $days;
        $this->actual_starting_date = $actual_starting_date;
        $this->assumed_end_date = $assumed_end_date;
        $this->actual_end_date = $actual_end_date;
        $this->productId = $productId;
        $this->postcode = $postcode;
        $this->projectId = $projectId;
        $this->worksPackages = $worksPackages;
        $this->comment = $comment;
        $this->type = $type;
        $this->attachment = $attachment;
        $this->project_attachment = $project_attachment;
        $this->status = $status;
        $this->scope = $scope;
    }

    public static function createFromArray(array $data, int $userId): SupplyFitEnquiryDto
    {
        return new SupplyFitEnquiryDto(
            $userId,
            (string)($data['days'] ?? ''),
            !empty($data['actual_starting_date']) ? $data['actual_starting_date'] : null,
            !empty($data['assumed_end_date']) ? $data['assumed_end_date'] : null,
            !empty($data['actual_end_date']) ? $data['actual_end_date'] : null,
            !empty($data['product_id']) ? (int) $data['product_id'] : null,
            ($data['postcode'] ?? ''),
            !empty($data['project_id']) ? (int) $data['project_id'] : null,
            !empty($data['works_packages']) ? json_decode($data['works_packages']) : [],
            ($data['comment'] ?? ''),
            ($data['type'] ?? ''),
            ($data['attachment'] ?? []),
            ($data['project_attachment'] ?? []),
            (int)($data['status'] ?? 1),
            (int)($data['scope'] ?? 1),
        );
    }

    public static function createFromRequest(Request $request, int $userId): SupplyFitEnquiryDto
    {
        $data = $request->validate([
            'postcode' => 'required',
            'product_id' => '',
            'days' => 'required',
            'actual_starting_date' => '',
            'assumed_end_date' => '',
            'actual_end_date' => '',
            'comment' => '',
            'project_id' => '',
            'works_packages' => '',
            'type' => 'required',
            'attachment' => '',
            'project_attachment' => '',
            'status' => '',
            'scope' => '',
        ]);

        return self::createFromArray($data, $userId);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getDays(): string
    {
        return $this->days;
    }

    public function getActualStartingDate(): ?string
    {
        return $this->actual_starting_date;
    }

    public function getAssumedEndDate(): ?string
    {
        return $this->assumed_end_date;
    }

    public function getActualEndDate(): ?string
    {
        return $this->actual_end_date;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function getPostcode(): string
    {
        return $this->postcode;
    }

    public function getProjectId(): ?int
    {
        return $this->projectId;
    }

    public function getWorksPackages(): array
    {
        return $this->worksPackages;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getAttachment(): array
    {
        return $this->attachment;
    }

    public function getProjectAttachment(): array
    {
        return $this->project_attachment;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getScope(): int
    {
        return $this->scope;
    }
}
