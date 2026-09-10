<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $manageUsers = new Permission();
        $manageUsers->name = 'Manage users';
        $manageUsers->slug = \App\Models\PermissionsReference::manageUsers;
        $manageUsers->save();

        $manageRoles = new Permission();
        $manageRoles->name = 'Manage roles';
        $manageRoles->slug = \App\Models\PermissionsReference::manageRoles;
        $manageRoles->save();

        $manageProducts = new Permission();
        $manageProducts->name = 'Manage products';
        $manageProducts->slug = \App\Models\PermissionsReference::manageProducts;
        $manageProducts->save();

        $readQ = new Permission();
        $readQ->name = 'Read Inquiry';
        $readQ->slug = \App\Models\PermissionsReference::readQuestion;
        $readQ->save();

        $readA = new Permission();
        $readA->name = 'Read Quote';
        $readA->slug = \App\Models\PermissionsReference::readAnswer;
        $readA->save();

        $ask = new Permission();
        $ask->name = 'Ask Inquiry';
        $ask->slug = \App\Models\PermissionsReference::askQuestion;
        $ask->save();

        $answ = new Permission();
        $answ->name = 'Answer Quote';
        $answ->slug = \App\Models\PermissionsReference::answerQuestion;
        $answ->save();

        $adminRole = Role::create([
            'name' => 'Administrator',
            'slug' => Role::ROLE_ADMIN_SLUG,
        ]);

        $adminRole->permissions()->attach([
            $manageRoles->id,
            $manageUsers->id,
            $answ->id,
            $ask->id,
            $readA->id,
            $readQ->id,
            $manageProducts->id
        ]);

        $user = Role::create([
            'name' => 'Sub-contractor',
            'slug' => Role::ROLE_USER_SLUG,
        ]);

        $company = Role::create([
            'name' => 'Merchant',
            'slug' => Role::ROLE_COMPANY_SLUG,
        ]);
        $user->permissions()->attach([$ask->id, $readQ->id, $readA->id]);
        $company->permissions()->attach([$answ->id, $readQ->id, $readA->id]);

        $john = User::create([
            'password' => Hash::make("12345"),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'postcode' => '10001',
            'email' => 'john@example.com',
            'username' => 'john',
            'company_number' => '',
            'activity_tracker_mapping' => '',
        ]);

        $jane = User::create([
            'password' => Hash::make("12345"),
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'postcode' => '10002',
            'email' => 'jane@example.com',
            'username' => 'jane',
            'company_number' => '',
            'activity_tracker_mapping' => '',
        ]);

        $jack = User::create([
            'password' => Hash::make("12345"),
            'first_name' => 'Jack',
            'last_name' => 'Reacher',
            'postcode' => '10003',
            'email' => 'jack@example.com',
            'username' => 'jack',
            'company_number' => '',
            'activity_tracker_mapping' => '',
        ]);

        $john->roles()->attach($user->id);
        $jane->roles()->attach($company->id);
        $jack->roles()->attach($adminRole->id);
    }
}
