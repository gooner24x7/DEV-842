<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemovePrefSuppliers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_preferred_suppliers', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('supplier_id');
            $table->boolean('active')->default(false);
            $table->primary(['user_id', 'supplier_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_preferred_supplier');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_preferred_suppliers');

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_preferred_supplier')->default(false);
        });
    }
}
