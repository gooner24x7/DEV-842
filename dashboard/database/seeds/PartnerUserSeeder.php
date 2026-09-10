<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PartnerUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $billingUserRolePermission = Permission::where(['slug' => 'manage-subscriptions'])->first();
        $readEnquiry = Permission::where(['slug' => \App\Models\PermissionsReference::readQuestion])->first();
        $readQuote = Permission::where(['slug' => \App\Models\PermissionsReference::readAnswer])->first();
        $createEnquiry = Permission::where(['slug' => \App\Models\PermissionsReference::askQuestion])->first();
        $partner = Role::create([
            'name' => 'Partner',
            'slug' => Role::ROLE_PARTNER,
        ]);

        $partner->permissions()->attach([$billingUserRolePermission->id, $readEnquiry->id, $readQuote->id, $createEnquiry->id]);

        $partnerUser = User::create([
            'company_number' => '',
            'activity_tracker_mapping' => '',
            'password' => Hash::make("12345"),
            'first_name' => 'Partner',
            'last_name' => '',
            'postcode' => '10003',
            'email' => 'partner@example.com',
            'username' => 'partner1',
            'can_assign_to_enquiries' => false,
        ]);

        $partnerUser->roles()->attach($partner->id);
    }
}
