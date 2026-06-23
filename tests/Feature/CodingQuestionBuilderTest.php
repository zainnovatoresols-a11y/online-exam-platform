<?php

namespace Tests\Feature;

use App\Enums\CodingDifficulty;
use App\Enums\QuestionType;
use App\Enums\TestStatus;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\Question;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CodingQuestionBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_coding_question_for_own_organization_draft_test(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->draftTestFor($admin, $organization);

        $response = $this->actingAs($admin)
            ->post(route('admin.tests.coding-questions.store', $test), $this->validPayload());

        $response->assertRedirect(route('admin.tests.questions.index', $test));

        $question = Question::query()->where('test_id', $test->id)->firstOrFail();

        $this->assertSame(QuestionType::Coding->value, $question->type);
        $this->assertSame('Reverse a string.', $question->body);
        $this->assertSame(CodingDifficulty::Easy->value, $question->difficulty);
        $this->assertSame(128000, $question->memory_limit_kb);
        $this->assertSame(['php', 'javascript'], $question->supported_languages);
        $this->assertSame('<?php echo "ready";', $question->starter_code['php']);
        $this->assertSame(2, $question->testCases()->count());
        $this->assertSame(1, $question->testCases()->where('is_hidden', false)->count());
        $this->assertSame(1, $question->testCases()->where('is_hidden', true)->count());
    }

    public function test_admin_can_create_coding_question_for_own_solo_draft_test(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $test = $this->draftTestFor($admin);

        $response = $this->actingAs($admin)
            ->post(route('admin.tests.coding-questions.store', $test), $this->validPayload());

        $response->assertRedirect(route('admin.tests.questions.index', $test));
        $this->assertDatabaseHas('questions', [
            'test_id' => $test->id,
            'type' => QuestionType::Coding->value,
        ]);
    }

    public function test_admin_cannot_create_coding_question_for_another_organizations_test(): void
    {
        $adminOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $adminOrganization);
        $otherAdmin = $this->userWithRole(UserRole::Admin, $otherOrganization);
        $test = $this->draftTestFor($otherAdmin, $otherOrganization);

        $this->actingAs($admin)
            ->post(route('admin.tests.coding-questions.store', $test), $this->validPayload())
            ->assertForbidden();
    }

    public function test_admin_cannot_create_coding_question_for_another_admins_solo_test(): void
    {
        $owner = $this->userWithRole(UserRole::Admin);
        $otherAdmin = $this->userWithRole(UserRole::Admin);
        $test = $this->draftTestFor($owner);

        $this->actingAs($otherAdmin)
            ->post(route('admin.tests.coding-questions.store', $test), $this->validPayload())
            ->assertForbidden();
    }

    public function test_admin_cannot_create_coding_question_for_published_test(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->draftTestFor($admin, $organization, [
            'status' => TestStatus::Published->value,
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.tests.coding-questions.store', $test), $this->validPayload())
            ->assertForbidden();
    }

    public function test_admin_can_create_coding_question_for_closed_test(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->draftTestFor($admin, $organization, [
            'status' => TestStatus::Closed->value,
            'published_at' => now()->subDay(),
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.tests.coding-questions.store', $test), $this->validPayload());

        $response->assertRedirect(route('admin.tests.questions.index', $test));
        $this->assertDatabaseHas('questions', [
            'test_id' => $test->id,
            'type' => QuestionType::Coding->value,
        ]);
    }

    public function test_coding_question_requires_at_least_one_visible_test_case(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->draftTestFor($admin, $organization);

        $response = $this->actingAs($admin)
            ->post(route('admin.tests.coding-questions.store', $test), $this->validPayload([
                'test_cases' => [
                    [
                        'input' => 'abc',
                        'expected_output' => 'cba',
                        'is_hidden' => true,
                        'points' => null,
                    ],
                ],
            ]));

        $response->assertSessionHasErrors('test_cases');
    }

    public function test_coding_question_requires_at_least_one_supported_language(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->draftTestFor($admin, $organization);

        $response = $this->actingAs($admin)
            ->post(route('admin.tests.coding-questions.store', $test), $this->validPayload([
                'supported_languages' => [],
                'starter_code' => [],
            ]));

        $response->assertSessionHasErrors('supported_languages');
    }

    public function test_starter_code_for_unselected_language_is_rejected(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->draftTestFor($admin, $organization);

        $response = $this->actingAs($admin)
            ->post(route('admin.tests.coding-questions.store', $test), $this->validPayload([
                'supported_languages' => ['php'],
                'starter_code' => [
                    'php' => '<?php',
                    'javascript' => 'function solve() {}',
                ],
            ]));

        $response->assertSessionHasErrors('starter_code.javascript');
    }

    public function test_admin_can_edit_coding_question_while_test_is_draft(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->draftTestFor($admin, $organization);
        $question = $this->codingQuestionFor($test);

        $response = $this->actingAs($admin)
            ->patch(route('admin.tests.coding-questions.update', [$test, $question]), $this->validPayload([
                'body' => 'Find the maximum number.',
                'marks' => 10,
                'difficulty' => CodingDifficulty::Medium->value,
                'supported_languages' => ['python'],
                'starter_code' => [
                    'python' => 'def solve(): pass',
                ],
                'test_cases' => [
                    [
                        'input' => "1 4 2\n",
                        'expected_output' => "4\n",
                        'is_hidden' => false,
                        'points' => 5,
                    ],
                ],
            ]));

        $response->assertRedirect(route('admin.tests.questions.index', $test));

        $question->refresh();
        $this->assertSame('Find the maximum number.', $question->body);
        $this->assertSame(10, $question->marks);
        $this->assertSame(CodingDifficulty::Medium->value, $question->difficulty);
        $this->assertSame(128000, $question->memory_limit_kb);
        $this->assertSame(['python'], $question->supported_languages);
        $this->assertSame(1, $question->testCases()->count());
    }

    public function test_backend_defaults_memory_limit_when_frontend_does_not_send_it(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->draftTestFor($admin, $organization);

        $response = $this->actingAs($admin)
            ->post(route('admin.tests.coding-questions.store', $test), $this->validPayload([
                'memory_limit_kb' => null,
            ]));

        $response->assertRedirect(route('admin.tests.questions.index', $test));

        $question = Question::query()->where('test_id', $test->id)->firstOrFail();

        $this->assertSame(128000, $question->memory_limit_kb);
    }

    public function test_edit_keeps_existing_memory_limit_when_frontend_does_not_send_it(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->draftTestFor($admin, $organization);
        $question = $this->codingQuestionFor($test, [
            'memory_limit_kb' => 256000,
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.tests.coding-questions.update', [$test, $question]), $this->validPayload([
                'body' => 'Keep memory limit while updating.',
                'memory_limit_kb' => null,
            ]));

        $response->assertRedirect(route('admin.tests.questions.index', $test));

        $this->assertSame(256000, $question->refresh()->memory_limit_kb);
    }

    public function test_admin_can_edit_coding_question_while_test_is_closed(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->draftTestFor($admin, $organization, [
            'status' => TestStatus::Closed->value,
            'published_at' => now()->subDay(),
            'closed_at' => now(),
        ]);
        $question = $this->codingQuestionFor($test);

        $response = $this->actingAs($admin)
            ->patch(route('admin.tests.coding-questions.update', [$test, $question]), $this->validPayload([
                'body' => 'Closed test coding update.',
            ]));

        $response->assertRedirect(route('admin.tests.questions.index', $test));
        $this->assertSame('Closed test coding update.', $question->refresh()->body);
    }

    public function test_admin_cannot_edit_coding_question_from_another_test(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->draftTestFor($admin, $organization);
        $otherTest = $this->draftTestFor($admin, $organization);
        $question = $this->codingQuestionFor($otherTest);

        $this->actingAs($admin)
            ->patch(route('admin.tests.coding-questions.update', [$test, $question]), $this->validPayload())
            ->assertNotFound();
    }

    public function test_admin_can_delete_coding_question_while_test_is_draft(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->draftTestFor($admin, $organization);
        $question = $this->codingQuestionFor($test);
        $testCaseId = $question->testCases()->firstOrFail()->id;

        $response = $this->actingAs($admin)
            ->delete(route('admin.tests.coding-questions.destroy', [$test, $question]));

        $response->assertRedirect(route('admin.tests.questions.index', $test));
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('question_test_cases', ['id' => $testCaseId]);
    }

    public function test_admin_can_delete_coding_question_while_test_is_closed(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->draftTestFor($admin, $organization, [
            'status' => TestStatus::Closed->value,
            'published_at' => now()->subDay(),
            'closed_at' => now(),
        ]);
        $question = $this->codingQuestionFor($test);

        $response = $this->actingAs($admin)
            ->delete(route('admin.tests.coding-questions.destroy', [$test, $question]));

        $response->assertRedirect(route('admin.tests.questions.index', $test));
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        $payload = [
            'body' => 'Reverse a string.',
            'marks' => 5,
            'order' => 1,
            'difficulty' => CodingDifficulty::Easy->value,
            'time_limit_ms' => 2000,
            'supported_languages' => ['php', 'javascript'],
            'starter_code' => [
                'php' => '<?php echo "ready";',
                'javascript' => 'function solve() {}',
            ],
            'test_cases' => [
                [
                    'input' => 'abc',
                    'expected_output' => 'cba',
                    'is_hidden' => false,
                    'points' => 2,
                ],
                [
                    'input' => 'hidden',
                    'expected_output' => 'neddih',
                    'is_hidden' => true,
                    'points' => 3,
                ],
            ],
        ];

        return [
            ...$payload,
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function draftTestFor(User $admin, ?Organization $organization = null, array $overrides = []): Test
    {
        return Test::factory()->create([
            'organization_id' => $organization?->id,
            'created_by_id' => $admin->id,
            'status' => TestStatus::Draft->value,
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function codingQuestionFor(Test $test, array $overrides = []): Question
    {
        $question = $test->questions()->create([
            'type' => QuestionType::Coding->value,
            'body' => 'Initial coding question.',
            'marks' => 5,
            'order' => 1,
            'difficulty' => CodingDifficulty::Easy->value,
            'time_limit_ms' => 2000,
            'memory_limit_kb' => 128000,
            'supported_languages' => ['php'],
            'starter_code' => ['php' => '<?php'],
            ...$overrides,
        ]);

        $question->testCases()->create([
            'input' => 'abc',
            'expected_output' => 'cba',
            'is_hidden' => false,
            'sort_order' => 1,
        ]);

        return $question;
    }

    private function userWithRole(UserRole $role, ?Organization $organization = null): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate($role->value, 'web');

        $user = User::factory()->create([
            'organization_id' => $organization?->id,
        ]);

        $user->assignRole($role->value);

        return $user;
    }
}
