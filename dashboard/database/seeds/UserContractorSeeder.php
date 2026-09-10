<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserContractorSeeder extends Seeder
{
    public function run()
    {
        $user = Role::where([
            'slug' => Role::ROLE_USER_SLUG,
        ])->first();

        $company = Role::where([
            'slug' => Role::ROLE_COMPANY_SLUG,
        ])->first();

        if ($user) {
            $user->name = 'Sub Contractor';
            $user->save();
        }

        if ($company) {
            $company->name = 'Merchant / Supplier';
            $company->save();
        }

        $reqContractor = new Permission();
        $reqContractor->name = 'Create Enquiry Contractor';
        $reqContractor->slug = 'ask-contractor';
        $reqContractor->save();

        $readContractor = new Permission();
        $readContractor->name = 'Read Enquiry Contractor';
        $readContractor->slug = 'read-contractor';
        $readContractor->save();

        $user = Role::where(['slug' => Role::ROLE_USER_SLUG])->first();
        $user->permissions()->attach($readContractor->id);

        $user = Role::where(['slug' => Role::ROLE_ADMIN_SLUG])->first();
        $user->permissions()->attach([$readContractor->id, $reqContractor->id]);

        $contractor = Role::create([
            'name' => 'Contractor',
            'slug' => Role::ROLE_CONTRACTOR,
        ]);
        $contractor->permissions()->attach([$reqContractor->id, $readContractor->id]);

        $paul = User::create([
            'password' => Hash::make("12345"),
            'first_name' => 'Paul',
            'last_name' => '',
            'postcode' => 'M60 1NW',
            'email' => 'paul@example.com',
            'username' => 'paul',
            'can_assign_to_enquiries' => false,
        ]);
        $paul->roles()->attach($contractor->id);
    }
}
