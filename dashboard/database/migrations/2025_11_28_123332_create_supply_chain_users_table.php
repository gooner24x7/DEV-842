<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplyChainUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supply_chain_users', function (Blueprint $table) {
            $table->id();
            $table->integer('role_id')->nullable(true)->default(null);
            $table->string('first_name')->nullable(true)->default(null);
            $table->string('last_name')->nullable(true)->default(null);
            $table->string('business_name')->nullable(true)->default(null);
            $table->string('email')->nullable(false)->unique();
            $table->string('phone')->nullable(true)->default(null);
            $table->string('addr_line_1')->nullable(true)->default(null);
            $table->string('addr_line_2')->nullable(true)->default(null);
            $table->string('city')->nullable(true)->default(null);
            $table->string('country')->nullable(true)->default(null);
            $table->string('postcode')->nullable(true)->default(null);
            $table->double('lat')->nullable(true)->default(null);
            $table->double('long')->nullable(true)->default(null);
            $table->integer('created_by')->nullable(false);
            $table->timestamps();
            $table->timestamp('converted_at')->nullable(true)->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supply_chain_users');
    }
}
