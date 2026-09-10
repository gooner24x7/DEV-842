<?php

use Illuminate\Database\Seeder;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

class CompaniesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userRole = Role::where(['slug' => Role::ROLE_COMPANY_SLUG])->first();
        if (!$userRole) {
            throw new Exception("No role");
        }

        if (($handle = fopen(__DIR__ . "/people-answering.csv", "r")) !== FALSE) {
            $i = 1;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $name     = $data[1];
                $postcode = $data[3];
                $lat      = $data[4];
                $long     = $data[5];

                $user = User::create([
                    'password'   => Hash::make("12345"),
                    'first_name' => "User {$name}",
                    'last_name'  => 'N/A',
                    'lat'        => (float)$lat, 
                    'long'       => (float)$long,
                    'postcode'   => $postcode,
                    'username'   => "user{$i}",
                    'email'      => "user{$i}@example.com",
                ]);

                $user->save();

                $user->roles()->sync($userRole->id);

                $i++;
            }

            fclose($handle);
        }
    }
}
