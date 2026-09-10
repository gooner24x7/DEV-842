<?php
declare(strict_types=1);

namespace App\Dto\Logistics;

use Carbon\Carbon;
use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Http\Request;

class LogisticsEnquiryDto
{
    private int $userId;
    private ?int $projectId;
    private ?int $worksPackageId;
    private int $type;
    private int $status;
    private int $scope;
    private string $collect_postcode;
    private string $collect_city;
    private string $collect_address1;
    private string $collect_address2;
    private string $collect_contact_name;
    private string $collect_contact_phone;
    private string $delivery_postcode;
    private string $delivery_city;
    private string $delivery_address1;
    private string $delivery_address2;
    private string $delivery_contact_name;
    private string $delivery_contact_phone;
    private string $vehicle_type;
    private string $load_details;
    private string $comments;
    private string $notes;
    private string $collect_date;
    private string $delivery_date;

    /** @var UploadedFile[] */
    private array $attachment;

    public function __construct(
        int    $userId,
        ?int   $projectId,
        ?int   $worksPackageId,
        int    $type,
        int    $status,
        int    $scope,
        string $collect_postcode,
        string $collect_city,
        string $collect_address1,
        string $collect_address2,
        string $collect_contact_name,
        string $collect_contact_phone,
        string $delivery_postcode,
        string $delivery_city,
        string $delivery_address1,
        string $delivery_address2,
        string $delivery_contact_name,
        string $delivery_contact_phone,
        string $vehicle_type,
        string $load_details,
        string $comments,
        string $notes,
        string $collect_date,
        string $delivery_date,
        array  $attachment,

    ) {
        $this->userId = $userId;
        $this->projectId = $projectId;
        $this->worksPackageId = $worksPackageId;
        $this->type = $type;
        $this->status = $status;
        $this->scope = $scope;
        $this->collect_postcode = $collect_postcode;
        $this->collect_city = $collect_city;
        $this->collect_address1 = $collect_address1;
        $this->collect_address2 = $collect_address2;
        $this->collect_contact_name = $collect_contact_name;
        $this->collect_contact_phone = $collect_contact_phone;
        $this->delivery_postcode = $delivery_postcode;
        $this->delivery_city = $delivery_city;
        $this->delivery_address1 = $delivery_address1;
        $this->delivery_address2 = $delivery_address2;
        $this->delivery_contact_name = $delivery_contact_name;
        $this->delivery_contact_phone = $delivery_contact_phone;
        $this->vehicle_type = $vehicle_type;
        $this->load_details = $load_details;
        $this->comments = $comments;
        $this->notes = $notes;
        $this->collect_date = $collect_date;
        $this->delivery_date = $delivery_date;
        $this->attachment = $attachment;
    }

    public static function createFromArray(array $data, int $userId): LogisticsEnquiryDto
    {
        return new LogisticsEnquiryDto(
            $userId,
            $data['project_id'] ? (int) $data['project_id'] : null,
            $data['works_package_id'] ? (int) $data['works_package_id'] : null,
            (int) $data['type'],
            (int) ($data['status'] ?? 0),
            (int) ($data['scope'] ?? 0),
            $data['collect_postcode'] ?? '',
            $data['collect_city'] ?? '',
            $data['collect_address1'] ?? '',
            $data['collect_address2'] ?? '',
            $data['collect_contact_name'] ?? '',
            $data['collect_contact_phone'] ?? '',
            $data['delivery_postcode'] ?? '',
            $data['delivery_city'] ?? '',
            $data['delivery_address1'] ?? '',
            $data['delivery_address2'] ?? '',
            $data['delivery_contact_name'] ?? '',
            $data['delivery_contact_phone'] ?? '',
            $data['vehicle_type'] ?? '',
            $data['load_details'] ?? '',
            $data['comments'] ?? '',
            $data['notes'] ?? '',
            $data['collect_date'] ?? '',
            $data['delivery_date'] ?? '',
            $data['attachment'] ?? []
        );
    }

    public static function createFromRequest(Request $request, int $userId): LogisticsEnquiryDto
    {
        $data = $request->validate([
            'project_id' => '',
            'works_package_id' => '',
            'type' => '',
            'status' => '',
            'scope' => '',
            'collect_postcode' => '',
            'collect_city' => '',
            'collect_address1' => '',
            'collect_address2' => '',
            'collect_contact_name' => '',
            'collect_contact_phone' => '',
            'delivery_postcode' => '',
            'delivery_city' => '',
            'delivery_address1' => '',
            'delivery_address2' => '',
            'delivery_contact_name' => '',
            'delivery_contact_phone' => '',
            'vehicle_type' => '',
            'load_details' => '',
            'comments' => '',
            'notes' => '',
            'collect_date' => '',
            'delivery_date' => '',
            'attachment' => '',
        ]);

        return new self(
            $userId,
            !empty($data['project_id']) ? (int) $data['project_id'] : null,
            !empty($data['works_package_id']) ? (int) $data['works_package_id'] : null,
            !empty($data['type']) ? (int) $data['type'] : 0,
            !empty($data['status']) ? (int) $data['status'] : 0,
            !empty($data['scope']) ? (int) $data['scope'] : 0,
            $data['collect_postcode'] ?? '',
            $data['collect_city'] ?? '',
            $data['collect_address1'] ?? '',
            $data['collect_address2'] ?? '',
            $data['collect_contact_name'] ?? '',
            $data['collect_contact_phone'] ?? '',
            $data['delivery_postcode'] ?? '',
            $data['delivery_city'] ?? '',
            $data['delivery_address1'] ?? '',
            $data['delivery_address2'] ?? '',
            $data['delivery_contact_name'] ?? '',
            $data['delivery_contact_phone'] ?? '',
            $data['vehicle_type'] ?? '',
            $data['load_details'] ?? '',
            $data['comments'] ?? '',
            $data['notes'] ?? '',
            $data['collect_date'] ?? '',
            $data['delivery_date'] ?? '',
            $data['attachment'] ?? []
        );
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getProjectId(): ?int
    {
        return $this->projectId;
    }

    public function getWorksPackageId(): ?int
    {
        return $this->worksPackageId;
    }

    public function getType(): int
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

    public function getCollectPostcode(): string
    {
        return $this->collect_postcode;
    }

    public function getCollectCity(): string
    {
        return $this->collect_city;
    }

    public function getCollectAddress1(): string
    {
        return $this->collect_address1;
    }

    public function getCollectAddress2(): string
    {
        return $this->collect_address2;
    }

    public function getCollectContactName(): string
    {
        return $this->collect_contact_name;
    }

    public function getCollectContactPhone(): string
    {
        return $this->collect_contact_phone;
    }

    public function getDeliveryPostcode(): string
    {
        return $this->delivery_postcode;
    }

    public function getDeliveryCity(): string
    {
        return $this->delivery_city;
    }

    public function getDeliveryAddress1(): string
    {
        return $this->delivery_address1;
    }

    public function getDeliveryAddress2(): string
    {
        return $this->delivery_address2;
    }

    public function getDeliveryContactName(): string
    {
        return $this->delivery_contact_name;
    }

    public function getDeliveryContactPhone(): string
    {
        return $this->delivery_contact_phone;
    }

    public function getVehicleType(): string
    {
        return $this->vehicle_type;
    }

    public function getLoadDetails(): string
    {
        return $this->load_details;
    }

    public function getComments(): string
    {
        return $this->comments;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getCollectDate(): ?Carbon
    {
        if ($this->collect_date) {
            $date = Carbon::createFromFormat('d-m-Y', $this->collect_date);
            if ($date) {
                return $date;
            }
        }

        return null;
    }

    public function getDeliveryDate(): ?Carbon
    {
        if ($this->delivery_date) {
            $date = Carbon::createFromFormat('d-m-Y', $this->delivery_date);
            if ($date) {
                return $date;
            }
        }

        return null;
    }

    public function getAttachment(): array
    {
        return $this->attachment;
    }
}
