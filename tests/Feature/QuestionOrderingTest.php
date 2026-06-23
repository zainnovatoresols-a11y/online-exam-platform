<?php

namespace Tests\Feature;

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

class QuestionOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reorder_mixed_questions_for_owned_draft_test(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->testFor($admin, $organization);

        $firstQuestion = $this->questionFor($test, [
            'body' => 'First question',
            'type' => QuestionType::Mcq->value,
            'order' => 1,
        ]);
        $secondQuestion = $this->questionFor($test, [
            'body' => 'Second question',
            'type' => QuestionType::Coding->value,
            'order' => 2,
        ]);
        $thirdQuestion = $this->questionFor($test, [
            'body' => 'Third question',
            'type' => QuestionType::Mcq->value,
            'order' => 3,
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.tests.questions.reorder', $test),
            [
                'question_ids' => [
                    $thirdQuestion->id,
                    $firstQuestion->id,
                    $secondQuestion->id,
                ],
            ],
        );

        $response->assertRedirect(route('admin.tests.questions.index', $test));

        $this->assertSame(
            [
                $thirdQuestion->id,
                $firstQuestion->id,
                $secondQuestion->id,
            ],
            $test->questions()
                ->orderBy('order')
                ->pluck('id')
                ->all(),
        );

        $this->assertSame(1, $thirdQuestion->refresh()->order);
        $this->assertSame(2, $firstQuestion->refresh()->order);
        $this->assertSame(3, $secondQuestion->refresh()->order);
    }

    public function test_admin_can_reorder_questions_for_closed_test(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->testFor($admin, $organization, [
            'status' => TestStatus::Closed->value,
            'published_at' => now()->subDay(),
            'closed_at' => now(),
        ]);

        $firstQuestion = $this->questionFor($test, [
            'body' => 'Closed test one',
            'order' => 1,
        ]);
        $secondQuestion = $this->questionFor($test, [
            'body' => 'Closed test two',
            'order' => 2,
        ]);

        $this->actingAs($admin)->patch(route('admin.tests.questions.reorder', $test), [
            'question_ids' => [$secondQuestion->id, $firstQuestion->id],
        ])->assertRedirect(route('admin.tests.questions.index', $test));

        $this->assertSame(
            [$secondQuestion->id, $firstQuestion->id],
            $test->questions()->orderBy('order')->pluck('id')->all(),
        );
    }

    public function test_admin_cannot_reorder_questions_for_published_test(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->testFor($admin, $organization, [
            'status' => TestStatus::Published->value,
            'published_at' => now(),
        ]);

        $firstQuestion = $this->questionFor($test, ['order' => 1]);
        $secondQuestion = $this->questionFor($test, ['order' => 2]);

        $this->actingAs($admin)->patch(route('admin.tests.questions.reorder', $test), [
            'question_ids' => [$secondQuestion->id, $firstQuestion->id],
        ])->assertForbidden();
    }

    public function test_admin_cannot_reorder_questions_outside_their_scope(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $otherAdmin = $this->userWithRole(UserRole::Admin, $otherOrganization);
        $test = $this->testFor($otherAdmin, $otherOrganization);

        $firstQuestion = $this->questionFor($test, ['order' => 1]);
        $secondQuestion = $this->questionFor($test, ['order' => 2]);

        $this->actingAs($admin)->patch(route('admin.tests.questions.reorder', $test), [
            'question_ids' => [$secondQuestion->id, $firstQuestion->id],
        ])->assertForbidden();
    }

    public function test_reorder_requires_the_complete_question_set_for_the_test(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = $this->testFor($admin, $organization);

        $firstQuestion = $this->questionFor($test, ['order' => 1]);
        $this->questionFor($test, ['order' => 2]);

        $response = $this->actingAs($admin)->patch(route('admin.tests.questions.reorder', $test), [
            'question_ids' => [$firstQuestion->id],
        ]);

        $response->assertSessionHasErrors('question_ids');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function testFor(User $admin, ?Organization $organization = null, array $overrides = []): Test
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
    private function questionFor(Test $test, array $overrides = []): Question
    {
        return Question::factory()->create([
            'test_id' => $test->id,
            ...$overrides,
        ]);
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
