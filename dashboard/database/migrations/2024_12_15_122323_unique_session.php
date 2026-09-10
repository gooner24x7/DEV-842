<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $results = DB::table('questionnaire_sessions')
            ->select('id', 'user_id', 'inquiry_id')
            ->orderBy('is_answered', 'desc')
            ->get();
        $map = [];
        foreach ($results as $result) {
            $map[$result->inquiry_id . '_' . $result->user_id][] = $result->id;
        }

        foreach ($map as $key => $value) {
            array_shift($value);
            foreach ($value as $id) {
                DB::table('questionnaire_sessions')->delete($id);
            }
        }

        Schema::table('questionnaire_sessions', function (Blueprint $table) {
            $table->unique(['user_id', 'inquiry_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questionnaire_sessions', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'inquiry_id']);
        });
    }
};
