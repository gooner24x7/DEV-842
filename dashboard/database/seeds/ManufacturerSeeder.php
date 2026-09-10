<?php

use Illuminate\Database\Seeder;

class ManufacturerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $manufacturerUserPerm = \App\Models\Permission::where(['slug' => 'manufacturer'])->first();
        if (!$manufacturerUserPerm) {
            $manufacturerUserPerm = new \App\Models\Permission();
            $manufacturerUserPerm->name = 'Manufacturer';
            $manufacturerUserPerm->slug = 'manufacturer';
            $manufacturerUserPerm->save();
        }

        $manufacturerRole = \App\Models\Role::where(['slug' => \App\Models\Role::ROLE_MANUFACTURER])->first();
        if (!$manufacturerRole) {
            $manufacturerRole = \App\Models\Role::create([
                'name' => 'Manufacturer',
                'slug' => \App\Models\Role::ROLE_MANUFACTURER,
            ]);
        }

        $manufacturerRole->permissions()->attach([$manufacturerUserPerm->id]);

        $user = \App\Models\User::create([
            'password' => \Illuminate\Support\Facades\Hash::make('12345'),
            'first_name' => 'Manufacturer',
            'last_name' => 'Manufacturer',
            'postcode' => 'M60 1NW',
            'email' => 'manufacturer@test.com',
            'username' => 'manufacturer1',
        ]);

        $user->roles()->attach($manufacturerRole->id);
    }
}
