<?php

use Illuminate\Database\Seeder;
use \App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use \App\Models\Permission;
use \App\Models\PermissionsReference;

class CustomerSuccessPlatform extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::where(['username' => 'csa_test'])->first();
        if ($user) {
            $user->delete();
        }
        $role = Role::where(['slug' => Role::ROLE_CUSTOMER_SUCCESS_ADMIN])->first();
        if ($role) {
            $role->delete();
        }

        $customerSuccessUser = User::create([
            'password' => Hash::make("12345"),
            'first_name' => 'CustomerSuccessAdmin',
            'last_name' => 'CustomerSuccessAdmin',
            'postcode' => '',
            'email' => 'csa_test@example.com',
            'username' => 'csa_test',
            'can_assign_to_enquiries' => false,
        ]);

        $customerSuccessRole = Role::create([
            'name' => 'Customer Success Administrator',
            'slug' => Role::ROLE_CUSTOMER_SUCCESS_ADMIN,
        ]);

        $readInquiry = Permission::where(['slug' => PermissionsReference::readQuestion])->first();
        $readQuote = Permission::where(['slug' => PermissionsReference::readAnswer])->first();
        $readSFInquiry = Permission::where(['slug' => PermissionsReference::readContractor])->first();

        $customerSuccessRole->permissions()->attach([
            $readInquiry->id,
            $readQuote->id,
            $readSFInquiry->id,
        ]);

        $customerSuccessUser->roles()->attach($customerSuccessRole->id);
    }
}
