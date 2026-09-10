<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Service\ProjectService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectServiceBoqDatabaseTest extends TestCase
{
    use DatabaseTransactions;

    public function testBoqHierarchyIsWrittenToNtukDev(): void
    {
        self::assertSame('mysql', config('database.default'));
        self::assertSame('ntuk_dev', config('database.connections.mysql.database'));

        $context = DB::table('projects')
            ->join('users', 'users.id', '=', 'projects.user_id')
            ->select('projects.id as project_id', 'users.id as user_id')
            ->first();

        self::assertNotNull($context, 'ntuk_dev must contain a project with its owning user.');

        $user = User::findOrFail((int) $context->user_id);
        $suffix = bin2hex(random_bytes(8));
        $parentName = 'PHPUnit BOQ parent ' . $suffix;
        $childName = 'PHPUnit BOQ child ' . $suffix;

        $createdIds = app(ProjectService::class)->storeWorksPackages([
            [
                'name' => $parentName,
                'children' => [
                    ['name' => $childName, 'children' => []],
                ],
            ],
        ], (int) $context->project_id, $user);

        self::assertCount(2, $createdIds);
        $this->assertDatabaseHas('works_packages', [
            'project_id' => (int) $context->project_id,
            'user_id' => (int) $context->user_id,
            'name' => $parentName,
            'parent_id' => null,
        ]);
        $this->assertDatabaseHas('works_packages', [
            'project_id' => (int) $context->project_id,
            'user_id' => (int) $context->user_id,
            'name' => $childName,
        ]);
    }
}
