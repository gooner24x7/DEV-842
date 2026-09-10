<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class RenameGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $user = Role::where([
            'slug' => Role::ROLE_USER_SLUG,
        ])->first();

        $company = Role::where([
            'slug' => Role::ROLE_COMPANY_SLUG,
        ])->first();

        $user->name = 'User (asking a question)';
        $user->save();

        $company->name = 'Company (answering)';
        $company->save();
    }
}
