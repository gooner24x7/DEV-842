<?php

use Illuminate\Database\Seeder;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

class BillingUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $billingUserRolePermission = Permission::where(['slug' => 'manage-subscriptions'])->first();

        if (!$billingUserRolePermission) {
            $billingUserRolePermission = new Permission();
            $billingUserRolePermission->name = 'Manage subscriptions';
            $billingUserRolePermission->slug = 'manage-subscriptions';
            $billingUserRolePermission->save();
        }

        $billingUserRole = Role::where(['slug' => Role::ROLE_BILLING_USER_SLUG])->first();
        if (!$billingUserRole) {
            $billingUserRole = Role::create([
                'name' => 'Billing User',
                'slug' => Role::ROLE_BILLING_USER_SLUG,
            ]);
        }


        $billingUserRole->permissions()->attach([$billingUserRolePermission->id]);


        $company = Role::where(['slug' => Role::ROLE_COMPANY_SLUG])->first();

        if (($handle = fopen(__DIR__ . "/billingcobranches.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                //"id","billing_company_name","first_name","last_name","postcode",
                //"email","email_verified_at","password","lat","long","remember_token","created_at",
                //"updated_at","username"
                $billingCompanyName = $data[1] ?? '';
                $firstName          = $data[2] ?? '';
                $lastName           = $data[3] ?? '';
                $postcode           = $data[4] ?? '';
                $email              = $data[5] ?? '';
                $password           = $data[7] ?? '';
                $lat                = $data[8] ?? '';
                $long               = $data[9] ?? '';
                $username           = $data[13] ?? '';

                $billingUsername    = \App\Service\UserService::slugify($billingCompanyName);

                $billingUser = User::where(['username' => $billingUsername])->first();
                if (!$billingUser) {
                    $billingUser = User::create([
                        'password'   => Hash::make("12345"),
                        'first_name' => $billingCompanyName,
                        'last_name'  => 'N/A',
                        'lat'        => 0,
                        'long'       => 0,
                        'postcode'   => '',
                        'username'   => $billingUsername,
                        'email'      => $email,
                    ]);
                    $billingUser->save();
                    $billingUser->roles()->sync($billingUserRole->id);

                    echo "created a billing user $billingUsername\n";
                }


                $user = User::where(['username' => $username])->first();
                if (!$user) {
                    $compUser = User::create([
                        'password'   => $password,
                        'first_name' => $firstName,
                        'last_name'  => $lastName,
                        'postcode'   => $postcode,
                        'email'      => $email,
                        'username'   => $username,
                        'billing_user_id' => $billingUser->id,
                    ]);

                    $compUser->roles()->attach($company->id);

                    echo "--created a company user $username\n";

                    continue;
                }

                $user->billing_user_id = $billingUser->id;
                $user->save();

                echo "--updated a company user $username\n";
            }

            fclose($handle);
        }
    }
}
