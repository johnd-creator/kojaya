<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskDependency;
use App\Models\User;
use App\Services\Auth\TokenAbilityResolver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class Sprint3ArchitectureHardeningTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_controllers_do_not_use_role_literals_for_access_decisions(): void
    {
        $offending = [];
        $root = app_path('Http/Controllers');

        foreach ($this->phpFiles($root) as $path => $contents) {
            if (preg_match('/->has(?:Any)?Role\s*\(/', $contents)) {
                $offending[] = str_replace(base_path().'/', '', $path);
            }
        }

        $this->assertSame([], $offending);
    }

    public function test_loan_policy_covers_operational_actions(): void
    {
        $loan = Loan::factory()->create();
        $manager = User::factory()->create();
        $manager->forceFill(['organization_id' => $loan->organization_id])->save();
        $manager->givePermissionTo(['view_cooperative_loan', 'manage_cooperative_loan']);
        $loanReviewer = User::factory()->create();
        $loanReviewer->forceFill(['organization_id' => $loan->organization_id])->save();
        $loanReviewer->givePermissionTo(['view_cooperative_loan', 'review_cooperative_loan']);
        $approver = User::factory()->create();
        $approver->forceFill(['organization_id' => $loan->organization_id])->save();
        $approver->givePermissionTo(['view_cooperative_loan', 'approve_cooperative_loan']);

        $this->assertTrue(Gate::forUser($manager)->allows('viewAny', Loan::class));
        $this->assertTrue(Gate::forUser($manager)->allows('manage', Loan::class));
        $this->assertTrue(Gate::forUser($manager)->allows('recordPayment', $loan));
        $this->assertFalse(Gate::forUser($manager)->allows('approve', $loan));
        $this->assertFalse(Gate::forUser($manager)->allows('managerReview', $loan));

        $this->assertTrue(Gate::forUser($loanReviewer)->allows('managerReview', $loan));
        $this->assertTrue(Gate::forUser($loanReviewer)->allows('reject', $loan));
        $this->assertFalse(Gate::forUser($loanReviewer)->allows('approve', $loan));
        $this->assertTrue(Gate::forUser($approver)->allows('approve', $loan));
        $this->assertTrue(Gate::forUser($approver)->allows('reject', $loan));
        $this->assertFalse(Gate::forUser($approver)->allows('disburse', $loan));
    }

    /**
     * @return array<string, array{0: array<int, string>, 1: string|null, 2: array<int, string>}>
     */
    public static function abilityProvider(): array
    {
        return [
            'work order viewer technician app' => [
                ['view_work_order_unit'],
                'technician',
                ['profile:read', 'work-orders:read', 'work-orders:write'],
            ],
            'cooperative operator default app' => [
                ['view_cooperative_member', 'manage_cooperative_payment', 'access_cooperative_pos', 'view_cooperative_report'],
                null,
                ['profile:read', 'cooperative.member.read', 'cooperative.payment.read', 'cooperative.payment.record', 'cooperative.report.read', 'cooperative.pos.read', 'cooperative.pos.write', 'cooperative:read', 'cooperative:write', 'reports:read', 'pos:read', 'pos:write'],
            ],
        ];
    }

    /**
     * @param  array<int, string>  $permissions
     * @param  array<int, string>  $expected
     */
    #[DataProvider('abilityProvider')]
    public function test_token_abilities_are_resolved_from_permissions(array $permissions, ?string $app, array $expected): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        $this->assertSame($expected, app(TokenAbilityResolver::class)->for($user, $app));
    }

    public function test_project_gantt_uses_dependency_model_for_links(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['organization_id' => $project->organization_id]);
        $user->givePermissionTo(['view_project_all', 'manage_project']);
        $predecessor = $this->task($project, 'Foundation');
        $task = $this->task($project, 'Structure');

        $response = $this->actingAs($user)->postJson(route('projects.gantt-link.store', $project), [
            'source' => $predecessor->id,
            'target' => $task->id,
            'type' => '1',
        ])->assertOk()
            ->assertJsonPath('action', 'inserted');

        $dependency = ProjectTaskDependency::query()->findOrFail($response->json('tid'));
        $this->assertSame('SS', $dependency->type);
        $this->assertSame($predecessor->id, $dependency->predecessor_id);
        $this->assertSame($task->id, $dependency->task_id);

        $this->actingAs($user)
            ->deleteJson(route('projects.gantt-link.destroy', [$project, $dependency->id]))
            ->assertOk()
            ->assertJsonPath('action', 'deleted');

        $this->assertDatabaseMissing('project_task_dependencies', ['id' => $dependency->id]);
    }

    public function test_project_vue_files_delegate_axios_to_api_modules(): void
    {
        $offending = [];
        $roots = [
            resource_path('js/pages/Project'),
            resource_path('js/components/project'),
        ];

        foreach ($roots as $root) {
            foreach ($this->frontendFiles($root) as $path => $contents) {
                if (preg_match('/axios\.(get|post|put|patch|delete)\s*\(/', $contents)) {
                    $offending[] = str_replace(resource_path('js').'/', '', $path);
                }
            }
        }

        $this->assertSame([], $offending);
    }

    private function task(Project $project, string $name): ProjectTask
    {
        return ProjectTask::query()->create([
            'project_id' => $project->id,
            'name' => $name,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'status' => 'PENDING',
            'progress_percentage' => 0,
            'sort_order' => 1,
        ]);
    }

    /**
     * @return iterable<string, string>
     */
    private function phpFiles(string $root): iterable
    {
        foreach ($this->files($root, ['php']) as $path => $contents) {
            yield $path => $contents;
        }
    }

    /**
     * @return iterable<string, string>
     */
    private function frontendFiles(string $root): iterable
    {
        foreach ($this->files($root, ['vue', 'ts']) as $path => $contents) {
            yield $path => $contents;
        }
    }

    /**
     * @param  array<int, string>  $extensions
     * @return iterable<string, string>
     */
    private function files(string $root, array $extensions): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), $extensions, true)) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            if ($contents !== false) {
                yield $file->getPathname() => $contents;
            }
        }
    }
}
