<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserViewInquiriesForBranchesSeeder extends Seeder
{
    public function run()
    {
        $branchManager = Role::where([
            'slug' => Role::ROLE_BRANCH_MANAGER,
        ])->first();

        if (!$branchManager) {
            $branchManager = Role::create([
                'name' => 'Branch Manager',
                'slug' => Role::ROLE_BRANCH_MANAGER,
            ]);
        }

        $manageBranches = new Permission();
        $manageBranches->name = 'Manage Branches';
        $manageBranches->slug = \App\Models\PermissionsReference::manageBranches;
        $manageBranches->save();


        $readQ = Permission::where(['slug' => \App\Models\PermissionsReference::readQuestion ])->first();
        $readA = Permission::where(['slug' => \App\Models\PermissionsReference::readAnswer ])->first();
        $ask = Permission::where(['slug' => \App\Models\PermissionsReference::askQuestion ])->first();
        $answ = Permission::where(['slug' => \App\Models\PermissionsReference::answerQuestion ])->first();

        $branchManager->permissions()->attach([$manageBranches->id, $readQ->id, $readA->id, $ask->id, $answ->id]);
    }
}
