<?php
declare(strict_types=1);

namespace App\Builder\Response;

use App\Models\User;
use Carbon\Carbon;

class UserResponseBuilder
{
    private User $user;
    private string $subscriptionName;

    public function __construct(User $user, string $subscriptionName)
    {
        $this->user = $user;
        $this->subscriptionName = $subscriptionName;
    }

    public static function getResponseForUser(User $user, string $subscriptionName): array
    {
        return (new self($user, $subscriptionName))->getResponse();
    }

    public function getResponse(): array
    {
        return [
            'id' => $this->user->getId(),
            'first_name' => $this->user->getFirstName(),
            'last_name' => $this->user->getLastName(),
            'postcode' => $this->user->getPostcode(),
            'phone' => $this->user->getPhone(),
            'city' => $this->user->getCity(),
            'country' => $this->user->getCountry(),
            'addr_line_1' => $this->user->getAddressLine1(),
            'addr_line_2' => $this->user->getAddressLine2(),
            'description' => $this->user->description,
            'locations' => $this->user->locations,
            'head_office_address' => $this->user->head_office_address,
            'customer_service' => $this->user->customer_service,
            'logo_url' => $this->user->logo_url,
            'username' => $this->user->getUsername(),
            'email' => $this->user->getEmail(),
            'roles' => $this->user->getRolesShortAttribute(),
            'permissions' => $this->user->getPermissions(),
            'product_ids' => $this->user->getProductIdsAttribute(),
            'role_name' => $this->user->getRoleNameAttribute(),
            'role_ids' => $this->user->getRoleIdsAttribute(),
            'billing_user_id' => $this->user->getBillingUserId(),
            'preferred_suppliers_file' => $this->user->getPreferredSuppliersFile(),
            'is_global' => $this->user->getIsGlobal(),
            'is_gold_account' => $this->user->getIsGoldAccount(),
            'is_test_account' => $this->user->getIsTestAccount(),
            'is_sme' => $this->user->getIsSme(),
            'manager_type' => $this->user->getManagerType(),
            'branch_id' => $this->user->getBranchId(),
            'branch_name' => $this->user->getBranchName(),
            'subs_name' => $this->subscriptionName,
            'can_assign_to_enquiries' => $this->user->getCanAssignToEnquiries(),
            'can_check_competency' => $this->user->can_check_competency,
            'trial_ends' => ($this->user->trial_ends) ? Carbon::createFromFormat('Y-m-d', $this->user->trial_ends)->format('d-m-Y') : '',
            'credit_application_form_url' => $this->user->credit_application_form_url,
            'is_quoted' => $this->user->is_quoted,
            'called_at' => $this->user->called_at,
            'company_number' => $this->user->company_number,
            'pipeline_of_work' => $this->user->pipeline_of_work,
            'potential_users' => $this->user->potential_users,
            'turnover' => $this->user->turnover,
            'number_of_employees' => $this->user->number_of_employees,
            'onboarding_call' => $this->user->onboarding_call,
            'pain_points' => $this->user->pain_points,
            'onboarding_active' => $this->user->onboarding_active,
            'activity_tracker_mapping' => $this->user->activity_tracker_mapping,
            'job_title' => $this->user->job_title,
            'alias' => $this->user->alias,
            'company_type' => $this->user->company_type,
        ];
    }
}
