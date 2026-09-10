<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logistics_contacts', function (Blueprint $table) {
            $table->id();
            $table->integer('enquiry_id')->nullable(false);
            $table->tinyInteger('type')->nullable(false)->default(0);
            $table->string('postcode', 10);
            $table->double('lat')->nullable(true)->default(null);
            $table->double('long')->nullable(true)->default(null);;
            $table->string('city', 50);
            $table->string('address1', 100);
            $table->string('address2', 100);
            $table->string('contact_name', 50);
            $table->string('contact_phone', 20);
        });

        Schema::create('logistics_enquiries', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable(false);
            $table->integer('project_id')->nullable(true);
            $table->integer('works_package_id')->nullable(true);
            $table->tinyInteger('type')->nullable(false)->default(0);
            $table->string('vehicle_type', 100);
            $table->string('load_details', 100);
            $table->text('comments');
            $table->text('notes');
            $table->dateTime('collect_date')->nullable(true);
            $table->dateTime('delivery_date')->nullable(true);
            $table->timestamps();
            $table->dateTime('archived_at')->nullable(true)->default(null);
        });

        Schema::create('logistics_quotes', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable(false);
            $table->integer('enquiry_id')->nullable(false);
            $table->tinyInteger('type')->nullable(false)->default(0);
            $table->double('price');
            $table->text('comments');
            $table->text('offers');
            $table->timestamps();
            $table->dateTime('viewed_at')->nullable(true)->default(null);
            $table->dateTime('accepted_at')->nullable(true)->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logistics_contacts');
        Schema::dropIfExists('logistics_enquiries');
        Schema::dropIfExists('logistics_quotes');
    }
};
