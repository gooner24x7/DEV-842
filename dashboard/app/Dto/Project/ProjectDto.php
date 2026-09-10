<?php
declare(strict_types=1);

namespace App\Dto\Project;

use Carbon\Carbon;
use Illuminate\Http\Request;

class ProjectDto
{
    private int $stage;
    private int $status;
    private int $level;
    private int $type;
    private string $name;
    private ?string $ocid;
    private ?string $postcode;
    private ?string $postcode_districts;
    private ?string $sector;
    private ?string $region;
    private ?string $contractor_region;
    private ?string $client_name;
    private ?string $framework;
    private ?string $boq_standard;
    private ?string $frame_type;
    private ?string $procurement_route;
    private ?float $project_value;
    private ?float $area_sqm;
    private ?float $target_miles_client;
    private ?float $target_miles_framework;
    private ?float $target_hours_ap;
    private ?float $target_hours_se;
    private ?float $budget_se;
    private ?float $perc_services;
    private ?float $perc_prelim;
    private ?float $perc_labour;
    private ?float $perc_materials;
    private ?string $date_start;
    private ?string $date_end;
    private ?string $date_end_tender;
    private ?string $description;

    public function __construct(
        int $stage,
        int $status,
        int $level,
        int $type,
        string $name,
        ?string $ocid,
        ?string $postcode,
        ?string $postcode_districts,
        ?string $sector,
        ?string $region,
        ?string $contractor_region,
        ?string $client_name,
        ?string $framework,
        ?string $boq_standard,
        ?string $frame_type,
        ?string $procurement_route,
        ?float $project_value,
        ?float $area_sqm,
        ?float $target_miles_client,
        ?float $target_miles_framework,
        ?float $target_hours_ap,
        ?float $target_hours_se,
        ?float $budget_se,
        ?float $perc_services,
        ?float $perc_prelim,
        ?float $perc_labour,
        ?float $perc_materials,
        ?string $date_start,
        ?string $date_end,
        ?string $date_end_tender,
        ?string $description,
    ) {
        $this->stage = $stage;
        $this->status = $status;
        $this->level = $level;
        $this->type = $type;
        $this->name = $name;
        $this->ocid = $ocid;
        $this->postcode = $postcode;
        $this->postcode_districts = $postcode_districts;
        $this->sector = $sector;
        $this->region = $region;
        $this->contractor_region = $contractor_region;
        $this->client_name = $client_name;
        $this->framework = $framework;
        $this->boq_standard = $boq_standard;
        $this->frame_type = $frame_type;
        $this->procurement_route = $procurement_route;
        $this->project_value = $project_value;
        $this->area_sqm = $area_sqm;
        $this->target_miles_client = $target_miles_client;
        $this->target_miles_framework = $target_miles_framework;
        $this->target_hours_ap = $target_hours_ap;
        $this->target_hours_se = $target_hours_se;
        $this->budget_se = $budget_se;
        $this->perc_services = $perc_services;
        $this->perc_prelim = $perc_prelim;
        $this->perc_labour = $perc_labour;
        $this->perc_materials = $perc_materials;
        $this->date_start = $date_start;
        $this->date_end = $date_end;
        $this->date_end_tender = $date_end_tender;
        $this->description = $description;
    }

    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'stage' => '',
            'status' => '',
            'level' => '',
            'type' => '',
            'name' => 'required|string',
            'ocid' => '',
            'postcode' => '',
            'postcode_districts' => '',
            'client_name' => '',
            'framework' => '',
            'sector' => '',
            'region' => '',
            'contractor_region' => '',
            'boq_standard' => '',
            'frame_type' => '',
            'procurement_route' => '',
            'project_value' => '',
            'area_sqm' => '',
            'target_miles_client' => '',
            'target_miles_framework' => '',
            'target_hours_ap' => '',
            'target_hours_se' => '',
            'budget_se' => '',
            'perc_services' => '',
            'perc_prelim' => '',
            'perc_labour' => '',
            'perc_materials' => '',
            'date_start' => '',
            'date_end' => '',
            'date_end_tender' => '',
            'description' => '',
        ]);

        return self::createFromArray($data);
    }

    public static function createFromArray(array $data): self
    {
        $dateStart = !empty($data['date_start']) ? Carbon::createFromFormat('d-m-Y', $data['date_start']) : null;
        $dateEnd = !empty($data['date_end']) ? Carbon::createFromFormat('d-m-Y', $data['date_end']) : null;
        $dateEndTender = !empty($data['date_end_tender']) ? Carbon::createFromFormat('d-m-Y', $data['date_end_tender']) : null;

        return new self(
            !empty($data['stage']) ? (int) $data['stage'] : 1,
            !empty($data['status']) ? (int) $data['status'] : 1,
            !empty($data['level']) ? (int) $data['level'] : 1,
            !empty($data['type']) ? (int) $data['type'] : 2,
            $data['name'],
            !empty($data['ocid']) ? $data['ocid'] : null,
            !empty($data['postcode']) ? $data['postcode'] : null,
            !empty($data['postcode_districts']) ? $data['postcode_districts'] : null,
            !empty($data['sector']) ? $data['sector'] : null,
            !empty($data['region']) ? $data['region'] : null,
            !empty($data['contractor_region']) ? $data['contractor_region'] : null,
            !empty($data['client_name']) ? $data['client_name'] : null,
            !empty($data['framework']) ? $data['framework'] : null,
            !empty($data['boq_standard']) ? $data['boq_standard'] : null,
            !empty($data['frame_type']) ? $data['frame_type'] : null,
            !empty($data['procurement_route']) ? $data['procurement_route'] : null,
            !empty($data['project_value']) ? (float) $data['project_value'] : null,
            !empty($data['area_sqm']) ? (float) $data['area_sqm'] : null,
            !empty($data['target_miles_client']) ? (float) $data['target_miles_client'] : null,
            !empty($data['target_miles_framework']) ? (float) $data['target_miles_framework'] : null,
            !empty($data['target_hours_ap']) ? (float) $data['target_hours_ap'] : null,
            !empty($data['target_hours_se']) ? (float) $data['target_hours_se'] : null,
            !empty($data['budget_se']) ? (float) $data['budget_se'] : null,
            !empty($data['perc_services']) ? (float) $data['perc_services'] : null,
            !empty($data['perc_prelim']) ? (float) $data['perc_prelim'] : null,
            !empty($data['perc_labour']) ? (float) $data['perc_labour'] : null,
            !empty($data['perc_materials']) ? (float) $data['perc_materials'] : null,
            !empty($dateStart) ? $dateStart->format('Y-m-d') : null,
            !empty($dateEnd) ? $dateEnd->format('Y-m-d') : null,
            !empty($dateEndTender) ? $dateEndTender->format('Y-m-d') : null,
            !empty($data['description']) ? $data['description'] : null,
        );
    }

    public function getStage(): int
    {
        return $this->stage;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOcid(): ?string
    {
        return $this->ocid;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function getPostcodeDistricts(): ?string
    {
        return $this->postcode_districts;
    }

    public function getSector(): ?string
    {
        return $this->sector;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getContractorRegion(): ?string
    {
        return $this->contractor_region;
    }

    public function getClientName(): ?string
    {
        return $this->client_name;
    }

    public function getFramework(): ?string
    {
        return $this->framework;
    }

    public function getBoqStandard(): ?string
    {
        return $this->boq_standard;
    }

    public function getFrameType(): ?string
    {
        return $this->frame_type;
    }

    public function getProcurementRoute(): ?string
    {
        return $this->procurement_route;
    }

    public function getProjectValue(): ?float
    {
        return $this->project_value;
    }

    public function getAreaSqm(): ?float
    {
        return $this->area_sqm;
    }

    public function getTargetMilesClient(): ?float
    {
        return $this->target_miles_client;
    }

    public function getTargetMilesFramework(): ?float
    {
        return $this->target_miles_framework;
    }

    public function getTargetHoursAp(): ?float
    {
        return $this->target_hours_ap;
    }

    public function getTargetHoursSe(): ?float
    {
        return $this->target_hours_se;
    }

    public function getBudgetSe(): ?float
    {
        return $this->budget_se;
    }

    public function getPercServices(): ?float
    {
        return $this->perc_services;
    }

    public function getPercPrelim(): ?float
    {
        return $this->perc_prelim;
    }

    public function getPercLabour(): ?float
    {
        return $this->perc_labour;
    }

    public function getPercMaterials(): ?float
    {
        return $this->perc_materials;
    }

    public function getDateStart(): ?string
    {
        return $this->date_start;
    }

    public function getDateEnd(): ?string
    {
        return $this->date_end;
    }

    public function getDateEndTender(): ?string
    {
        return $this->date_end_tender;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
