<?php
declare(strict_types=1);

namespace App\Dto\Onboarding;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ContractorOnboardingDto
{
    private ?string $company_name;
    private ?string $company_address1;
    private ?string $company_address2;
    private ?string $company_city;
    private ?string $company_postcode;
    private ?string $company_registration_number;
    private ?string $company_vat_number;
    private int $developer;
    private int $framework;
    private int $main_contractor;
    private int $sub_contractor;
    private int $merchant;
    private int $manufacturer;
    private int $is_sme;
    private int $goods_supply;
    private int $cas_approved;
    private int $cas_certifying_body;
    private ?string $cas_issue_date;
    private ?string $cas_expiry_date;
    private ?UploadedFile $file_cas;
    private ?UploadedFile $file_cyber;
    private ?UploadedFile $file_ssip;
    private ?UploadedFile $file_fire_products;
    private ?UploadedFile $file_fire_competencies;
    private ?UploadedFile $file_cscs;
    private ?UploadedFile $file_induction;
    private ?UploadedFile $file_modern_slavery;
    private ?UploadedFile $file_bribery;
    private ?UploadedFile $file_conduct;
    private ?UploadedFile $file_mod;
    private ?UploadedFile $file_pref_suppliers;
    private ?UploadedFile $file_user_roles;

    public function __construct(
        ?string $company_name,
        ?string $company_address1,
        ?string $company_address2,
        ?string $company_city,
        ?string $company_postcode,
        ?string $company_registration_number,
        ?string $company_vat_number,
        int $developer,
        int $framework,
        int $main_contractor,
        int $sub_contractor,
        int $merchant,
        int $manufacturer,
        int $is_sme,
        int $goods_supply,
        int $cas_approved,
        int $cas_certifying_body,
        ?string $cas_issue_date,
        ?string $cas_expiry_date,
        ?UploadedFile $file_cas,
        ?UploadedFile $file_cyber,
        ?UploadedFile $file_ssip,
        ?UploadedFile $file_fire_products,
        ?UploadedFile $file_fire_competencies,
        ?UploadedFile $file_cscs,
        ?UploadedFile $file_induction,
        ?UploadedFile $file_modern_slavery,
        ?UploadedFile $file_bribery,
        ?UploadedFile $file_conduct,
        ?UploadedFile $file_mod,
        ?UploadedFile $file_pref_suppliers,
        ?UploadedFile $file_user_roles,
    ) {
        $this->company_name = $company_name;
        $this->company_address1 = $company_address1;
        $this->company_address2 = $company_address2;
        $this->company_city = $company_city;
        $this->company_postcode = $company_postcode;
        $this->company_registration_number = $company_registration_number;
        $this->company_vat_number = $company_vat_number;
        $this->developer = $developer;
        $this->framework = $framework;
        $this->main_contractor = $main_contractor;
        $this->sub_contractor = $sub_contractor;
        $this->merchant = $merchant;
        $this->manufacturer = $manufacturer;
        $this->is_sme = $is_sme;
        $this->goods_supply = $goods_supply;
        $this->cas_approved = $cas_approved;
        $this->cas_certifying_body = $cas_certifying_body;
        $this->cas_issue_date = $cas_issue_date;
        $this->cas_expiry_date = $cas_expiry_date;
        $this->file_cas = $file_cas;
        $this->file_cyber = $file_cyber;
        $this->file_ssip = $file_ssip;
        $this->file_fire_products = $file_fire_products;
        $this->file_fire_competencies = $file_fire_competencies;
        $this->file_cscs = $file_cscs;
        $this->file_induction = $file_induction;
        $this->file_modern_slavery = $file_modern_slavery;
        $this->file_bribery = $file_bribery;
        $this->file_conduct = $file_conduct;
        $this->file_mod = $file_mod;
        $this->file_pref_suppliers = $file_pref_suppliers;
        $this->file_user_roles = $file_user_roles;
    }

    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'company_name' => '',
            'company_address1' => '',
            'company_address2' => '',
            'company_city' => '',
            'company_postcode' => '',
            'company_registration_number' => '',
            'company_vat_number' => '',
            'developer' => '',
            'framework' => '',
            'main_contractor' => '',
            'sub_contractor' => '',
            'merchant' => '',
            'manufacturer' => '',
            'is_sme' => '',
            'goods_supply' => '',
            'cas_approved' => '',
            'cas_certifying_body' => '',
            'cas_issue_date' => '',
            'cas_expiry_date' => '',
            'file_cas' => '',
            'file_cyber' => '',
            'file_ssip' => '',
            'file_fire_products' => '',
            'file_fire_competencies' => '',
            'file_cscs' => '',
            'file_induction' => '',
            'file_modern_slavery' => '',
            'file_bribery' => '',
            'file_conduct' => '',
            'file_mod' => '',
            'file_pref_suppliers' => '',
            'file_user_roles' => '',
        ]);

        return self::createFromArray($data);
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            $data['company_name'] ?? null,
            $data['company_address1'] ?? null,
            $data['company_address2'] ?? null,
            $data['company_city'] ?? null,
            $data['company_postcode'] ?? null,
            $data['company_registration_number'] ?? null,
            $data['company_vat_number'] ?? null,
            !empty($data['developer']) ? (int) $data['developer'] : 0,
            !empty($data['framework']) ? (int) $data['framework'] : 0,
            !empty($data['main_contractor']) ? (int) $data['main_contractor'] : 0,
            !empty($data['sub_contractor']) ? (int) $data['sub_contractor'] : 0,
            !empty($data['merchant']) ? (int) $data['merchant'] : 0,
            !empty($data['manufacturer']) ? (int) $data['manufacturer'] : 0,
            !empty($data['is_sme']) ? (int) $data['is_sme'] : 0,
            !empty($data['goods_supply']) ? (int) $data['goods_supply'] : 0,
            !empty($data['cas_approved']) ? (int) $data['cas_approved'] : 0,
            !empty($data['cas_certifying_body']) ? (int) $data['cas_certifying_body'] : 0,
            $data['cas_issue_date'] ?? null,
            $data['cas_expiry_date'] ?? null,
            $data['file_cas'] ?? null,
            $data['file_cyber'] ?? null,
            $data['file_ssip'] ?? null,
            $data['file_fire_products'] ?? null,
            $data['file_fire_competencies'] ?? null,
            $data['file_cscs'] ?? null,
            $data['file_induction'] ?? null,
            $data['file_modern_slavery'] ?? null,
            $data['file_bribery'] ?? null,
            $data['file_conduct'] ?? null,
            $data['file_mod'] ?? null,
            $data['file_pref_suppliers'] ?? null,
            $data['file_user_roles'] ?? null,
        );
    }

    public function getCompanyName(): ?string
    {
        return $this->company_name;
    }

    public function getCompanyAddress1(): ?string
    {
        return $this->company_address1;
    }

    public function getCompanyAddress2(): ?string
    {
        return $this->company_address2;
    }

    public function getCompanyCity(): ?string
    {
        return $this->company_city;
    }

    public function getCompanyPostcode(): ?string
    {
        return $this->company_postcode;
    }

    public function getCompanyRegistrationNumber(): ?string
    {
        return $this->company_registration_number;
    }

    public function getCompanyVatNumber(): ?string
    {
        return $this->company_vat_number;
    }

    public function getDeveloper(): int
    {
        return $this->developer;
    }

    public function getFramework(): int
    {
        return $this->framework;
    }

    public function getMainContractor(): int
    {
        return $this->main_contractor;
    }

    public function getSubContractor(): int
    {
        return $this->sub_contractor;
    }

    public function getMerchant(): int
    {
        return $this->merchant;
    }

    public function getManufacturer(): int
    {
        return $this->manufacturer;
    }

    public function getIsSme(): int
    {
        return $this->is_sme;
    }

    public function getGoodsSupply(): int
    {
        return $this->goods_supply;
    }

    public function getCasApproved(): int
    {
        return $this->cas_approved;
    }

    public function getCasCertifyingBody(): int
    {
        return $this->cas_certifying_body;
    }

    public function getCasIssueDate(): ?string
    {
        return $this->cas_issue_date;
    }

    public function getCasExpiryDate(): ?string
    {
        return $this->cas_expiry_date;
    }

    public function getFileCas(): ?UploadedFile
    {
        return $this->file_cas;
    }

    public function getFileCyber(): ?UploadedFile
    {
        return $this->file_cyber;
    }

    public function getFileSsip(): ?UploadedFile
    {
        return $this->file_ssip;
    }

    public function getFileFireProducts(): ?UploadedFile
    {
        return $this->file_fire_products;
    }

    public function getFileFireCompetencies(): ?UploadedFile
    {
        return $this->file_fire_competencies;
    }

    public function getFileCscs(): ?UploadedFile
    {
        return $this->file_cscs;
    }

    public function getFileInduction(): ?UploadedFile
    {
        return $this->file_induction;
    }

    public function getFileModernSlavery(): ?UploadedFile
    {
        return $this->file_modern_slavery;
    }

    public function getFileBribery(): ?UploadedFile
    {
        return $this->file_bribery;
    }

    public function getFileConduct(): ?UploadedFile
    {
        return $this->file_conduct;
    }

    public function getFileMod(): ?UploadedFile
    {
        return $this->file_mod;
    }

    public function getFilePrefSuppliers(): ?UploadedFile
    {
        return $this->file_pref_suppliers;
    }

    public function getFileUserRoles(): ?UploadedFile
    {
        return $this->file_user_roles;
    }
}
