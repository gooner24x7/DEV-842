<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $results = DB::table('questionnaires')->select('project_tag', 'user_id')->distinct()->get();
        foreach ($results as $result) {
            $project = $result->project_tag;
            $worksPackageId = DB::table('works_packages')->insertGetId([
                'project_tag' => $project,
                'name' => $project,
                'user_id' => $result->user_id,
            ]);

            DB::table('questionnaires')->where('project_tag', $project)
                ->update(['works_package_id' => $worksPackageId]);
            DB::table('questionnaire_sessions')->where('project_tag', $project)
                ->update(['works_package_id' => $worksPackageId]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
