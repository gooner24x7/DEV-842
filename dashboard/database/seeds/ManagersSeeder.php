<?php

use Illuminate\Database\Seeder;

use App\Models\Role;
use App\Models\Permission;

class ManagersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $managerRolePermission = Permission::where(['slug' => 'manage-unit'])->first();

        if (!$managerRolePermission) {
            $managerRolePermission = new Permission();
            $managerRolePermission->name = 'View reports';
            $managerRolePermission->slug = 'view-reports';
            $managerRolePermission->save();
        }

        $managerRole = Role::where(['slug' => Role::ROLE_MANAGER])->first();
        if (!$managerRole) {
            $managerRole = Role::create([
                'name' => 'Manager branch/area/company',
                'slug' => Role::ROLE_MANAGER,
            ]);
        }

        $managerRole->permissions()->attach([$managerRolePermission->id]);
    }
}
