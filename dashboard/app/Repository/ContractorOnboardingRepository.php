<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\Onboarding\ContractorOnboardingDto;
use App\Dto\SearchParamsDto;
use App\Models\Certificate;
use App\Models\ContractorOnboarding;
use App\Models\Role;
use App\Models\User;
use Bnb\Laravel\Attachments\Attachment;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class ContractorOnboardingRepository
{
    const string DEFAULT_ORDER_FIELD_NAME = 'id';
    const int ITEMS_PER_PAGE = 20;

    /**
     * @param SearchParamsDto $searchParamsDto
     * @return LengthAwarePaginator
     */
    public function find(SearchParamsDto $searchParamsDto): LengthAwarePaginator
    {
        $query = ContractorOnboarding::query()->select('*');

        $query->orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }

    public function getByUserId(int $userId): ?ContractorOnboarding
    {
        $query = ContractorOnboarding::query()->select('*')->where('user_id', $userId);

        return $query->get()->first();
    }

    /**
     * @param User $user
     * @param ContractorOnboardingDto $dto
     * @return ContractorOnboarding
     */
    public function create(User $user, ContractorOnboardingDto $dto): ContractorOnboarding
    {
        $casIssueDate = !empty($dto->getCasIssueDate()) ? Carbon::createFromFormat('d-m-Y', $dto->getCasIssueDate()) : null;
        $casExpiryDate = !empty($dto->getCasExpiryDate()) ? Carbon::createFromFormat('d-m-Y', $dto->getCasExpiryDate()) : null;

        $onboarding = ContractorOnboarding::create([
            'user_id' => $user->getId(),
            'company_name' => $dto->getCompanyName(),
            'company_address1' => $dto->getCompanyAddress1(),
            'company_address2' => $dto->getCompanyAddress2(),
            'company_city' => $dto->getCompanyCity(),
            'company_postcode' => $dto->getCompanyPostcode(),
            'company_registration_number' => $dto->getCompanyRegistrationNumber(),
            'company_vat_number' => $dto->getCompanyVatNumber(),
            'developer' => $dto->getDeveloper(),
            'framework' => $dto->getFramework(),
            'main_contractor' => $dto->getMainContractor(),
            'sub_contractor' => $dto->getSubContractor(),
            'merchant' => $dto->getMerchant(),
            'manufacturer' => $dto->getManufacturer(),
            'goods_supply' => $dto->getGoodsSupply(),
            'cas_approved' => $dto->getCasApproved(),
            'cas_certifying_body' => $dto->getCasCertifyingBody(),
            'cas_issue_date' => $casIssueDate ? $casIssueDate->format('Y-m-d') : null,
            'cas_expiry_date' => $casExpiryDate ? $casExpiryDate->format('Y-m-d') : null,
        ]);

        $companyUserIds = $user->getCompanyUsers();
        $companyUserIds[] = $user->getId();
        User::whereIn('id', $companyUserIds)->update(['is_sme' => $dto->getIsSme()]);

        if ($dto->getGoodsSupply()) {
            $certData = [
                'user_id' => $user->getId(),
                'type' => 'goods_supply',
                'cert_no' => '',
                'authority' => ''
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileCas()) {
            $fileCas = $onboarding->attach($dto->getFileCas(), ['description' => 'cas']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'cas',
                'cert_no' => '',
                'authority' => $onboarding->cas_certifying_body_str,
                'url' => $fileCas->url,
                'issue_date' => $casIssueDate ? $casIssueDate->format('Y-m-d') : null,
                'expiry_date' => $casExpiryDate ? $casExpiryDate->format('Y-m-d') : null,
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileCscs()) {
            $fileCscs = $onboarding->attach($dto->getFileCscs(), ['description' => 'cscs']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'cscs',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileCscs->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileSsip()) {
            $fileSsip = $onboarding->attach($dto->getFileSsip(), ['description' => 'ssip']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'ssip',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileSsip->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileCyber()) {
            $fileCyber = $onboarding->attach($dto->getFileCyber(), ['description' => 'cyber']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'cyber',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileCyber->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileFireProducts()) {
            $fileFireProducts = $onboarding->attach($dto->getFileFireProducts(), ['description' => 'fire_products']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'fire_products',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileFireProducts->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileFireCompetencies()) {
            $fileFireComp = $onboarding->attach($dto->getFileFireCompetencies(), ['description' => 'fire_competencies']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'fire_competencies',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileFireComp->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileInduction()) {
            $fileInduction = $onboarding->attach($dto->getFileInduction(), ['description' => 'induction']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'induction',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileInduction->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileModernSlavery()) {
            $fileModernSlavery = $onboarding->attach($dto->getFileModernSlavery(), ['description' => 'modern_slavery']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'modern_slavery',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileModernSlavery->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileBribery()) {
            $fileBribery = $onboarding->attach($dto->getFileBribery(), ['description' => 'bribery']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'bribery',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileBribery->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileConduct()) {
            $fileConduct = $onboarding->attach($dto->getFileConduct(), ['description' => 'conduct']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'conduct',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileConduct->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileMod()) {
            $fileMod = $onboarding->attach($dto->getFileMod(), ['description' => 'mod']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'mod',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileMod->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFilePrefSuppliers()) {
            $filePrefSuppliers = $onboarding->attach($dto->getFilePrefSuppliers(), ['description' => 'pref_suppliers']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'pref_suppliers',
                'cert_no' => '',
                'authority' => '',
                'url' => $filePrefSuppliers->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileUserRoles()) {
            $onboarding->attach($dto->getFileUserRoles(), ['description' => 'user_roles']);
        }

        $user->onboardingChecklist()->update(['form_complete' => true]);

        return $onboarding;
    }

    /**
     * @param int $id
     * @param User $user
     * @param ContractorOnboardingDto $dto
     * @return ContractorOnboarding|null
     */
    public function update(int $id, User $user, ContractorOnboardingDto $dto): ?ContractorOnboarding
    {
        $onboarding = ContractorOnboarding::find($id);

        if (!$onboarding) {
            return null;
        }

        $casIssueDate = !empty($dto->getCasIssueDate()) ? Carbon::createFromFormat('d-m-Y', $dto->getCasIssueDate()) : null;
        $casExpiryDate = !empty($dto->getCasExpiryDate()) ? Carbon::createFromFormat('d-m-Y', $dto->getCasExpiryDate()) : null;

        $onboarding->update([
            'company_name' => $dto->getCompanyName(),
            'company_address1' => $dto->getCompanyAddress1(),
            'company_address2' => $dto->getCompanyAddress2(),
            'company_city' => $dto->getCompanyCity(),
            'company_postcode' => $dto->getCompanyPostcode(),
            'company_registration_number' => $dto->getCompanyRegistrationNumber(),
            'company_vat_number' => $dto->getCompanyVatNumber(),
            'developer' => $dto->getDeveloper(),
            'framework' => $dto->getFramework(),
            'main_contractor' => $dto->getMainContractor(),
            'sub_contractor' => $dto->getSubContractor(),
            'merchant' => $dto->getMerchant(),
            'manufacturer' => $dto->getManufacturer(),
            'cas_approved' => $dto->getCasApproved(),
            'cas_certifying_body' => $dto->getCasCertifyingBody(),
            'goods_supply' => $dto->getGoodsSupply(),
            'cas_issue_date' => $casIssueDate ? $casIssueDate->format('Y-m-d') : null,
            'cas_expiry_date' => $casExpiryDate ? $casExpiryDate->format('Y-m-d') : null,
        ]);

        $companyUserIds = $user->getCompanyUsers();
        $companyUserIds[] = $user->getId();
        User::whereIn('id', $companyUserIds)->update(['is_sme' => $dto->getIsSme()]);

        if ($dto->getGoodsSupply()) {
            $certData = [
                'user_id' => $user->getId(),
                'type' => 'goods_supply',
                'cert_no' => '',
                'authority' => ''
            ];

            $this->updateCertificates($user, $certData);
        }

        $fileCas = null;

        if ($dto->getFileCas()) {
            $this->deleteOnboardingFile($id, $user->getId(), 'cas');
            $fileCas = $onboarding->attach($dto->getFileCas(), ['description' => 'cas']);
        }

        if ($dto->getCasExpiryDate() || $dto->getCasIssueDate() || $dto->getCasCertifyingBody()) {
            $certData = [
                'user_id' => $user->getId(),
                'type' => 'cas',
                'cert_no' => '',
                'authority' => $onboarding->cas_certifying_body_str,
                'issue_date' => $casIssueDate ? $casIssueDate->format('Y-m-d') : null,
                'expiry_date' => $casExpiryDate ? $casExpiryDate->format('Y-m-d') : null
            ];

            if ($fileCas) {
                $certData['url'] = $fileCas->url;
            }

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileCscs()) {
            $this->deleteOnboardingFile($id, $user->getId(), 'cscs');
            $fileCscs = $onboarding->attach($dto->getFileCscs(), ['description' => 'cscs']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'cscs',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileCscs->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileSsip()) {
            $this->deleteOnboardingFile($id, $user->getId(), 'ssip');
            $fileSsip = $onboarding->attach($dto->getFileSsip(), ['description' => 'ssip']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'ssip',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileSsip->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileCyber()) {
            $this->deleteOnboardingFile($id, $user->getId(), 'cyber');
            $fileCyber = $onboarding->attach($dto->getFileCyber(), ['description' => 'cyber']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'cyber',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileCyber->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileFireProducts()) {
            $this->deleteOnboardingFile($id, $user->getId(), 'fire_products');
            $fileFireProducts = $onboarding->attach($dto->getFileFireProducts(), ['description' => 'fire_products']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'fire_products',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileFireProducts->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileFireCompetencies()) {
            $this->deleteOnboardingFile($id, $user->getId(), 'fire_competencies');
            $fileFireComp = $onboarding->attach($dto->getFileFireCompetencies(), ['description' => 'fire_competencies']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'fire_competencies',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileFireComp->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileInduction()) {
            $this->deleteOnboardingFile($id, $user->getId(), 'induction');
            $fileInduction = $onboarding->attach($dto->getFileInduction(), ['description' => 'induction']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'induction',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileInduction->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileModernSlavery()) {
            $this->deleteOnboardingFile($id, $user->getId(), 'modern_slavery');
            $fileModernSlavery = $onboarding->attach($dto->getFileModernSlavery(), ['description' => 'modern_slavery']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'modern_slavery',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileModernSlavery->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileBribery()) {
            $this->deleteOnboardingFile($id, $user->getId(), 'bribery');
            $fileBribery = $onboarding->attach($dto->getFileBribery(), ['description' => 'bribery']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'bribery',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileBribery->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileConduct()) {
            $this->deleteOnboardingFile($id, $user->getId(), 'conduct');
            $fileConduct = $onboarding->attach($dto->getFileConduct(), ['description' => 'conduct']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'conduct',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileConduct->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileMod()) {
            $this->deleteOnboardingFile($id, $user->getId(), 'mod');
            $fileMod = $onboarding->attach($dto->getFileMod(), ['description' => 'mod']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'mod',
                'cert_no' => '',
                'authority' => '',
                'url' => $fileMod->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFilePrefSuppliers()) {
            $this->deleteOnboardingFile($id, $user->getId(), 'pref_suppliers');;
            $filePrefSuppliers = $onboarding->attach($dto->getFilePrefSuppliers(), ['description' => 'pref_suppliers']);

            $certData = [
                'user_id' => $user->getId(),
                'type' => 'pref_suppliers',
                'cert_no' => '',
                'authority' => '',
                'url' => $filePrefSuppliers->url
            ];

            $this->updateCertificates($user, $certData);
        }

        if ($dto->getFileUserRoles()) {
            $this->deleteOnboardingFile($id, $user->getId(), 'user_roles');;
            $onboarding->attach($dto->getFileUserRoles(), ['description' => 'user_roles']);
        }

        return $onboarding;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $onboarding = ContractorOnboarding::find($id);

        if (!$onboarding) {
            return false;
        }

        return (bool) $onboarding->delete();
    }

    private function updateCertificates(User $user, array $certData): void
    {
        if ($user->hasRole(Role::ROLE_BILLING_USER_SLUG)) {
            $companyUserIds = $user->getCompanyUsers();

            foreach($companyUserIds as $companyUserId) {
                $userCertData = $certData;
                $userCertData['user_id'] = $companyUserId;

                $cert = Certificate::where(['user_id' => $companyUserId, 'type' => $userCertData['type']])->get()->first();

                if ($cert) {
                    $cert->update($userCertData);
                } else {
                    Certificate::create($userCertData);
                }

                //Certificate::updateOrCreate($userCertData, ['user_id' => $companyUserId, 'type' => $userCertData['type']]);
            }
        } else {
            $cert = Certificate::where(['user_id' => $user->getId(), 'type' => $certData['type']])->get()->first();

            if ($cert) {
                $cert->update($certData);
            } else {
                Certificate::create($certData);
            }

            //Certificate::updateOrCreate($certData, ['user_id' => $certData['user_id'], 'type' => $certData['type']]);
        }
    }

    private function deleteOnboardingFile(int $id, int $userId, string $type): void
    {
        Attachment::where([
            'model_type' => ContractorOnboarding::class,
            'model_id' => $id,
            'description' => $type,
        ])->delete();
    }
}
