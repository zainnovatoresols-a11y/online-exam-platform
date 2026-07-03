<?php

namespace Tests\Feature;

use App\Enums\TestStatus;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\ProctoringRecording;
use App\Models\ProctoringRecordingChunk;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TestManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_test_for_their_organization(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $startsAt = now()->addDay()->seconds(0);

        $response = $this->actingAs($admin)->post(route('admin.tests.store'), [
            'title' => 'Laravel Basics',
            'description' => 'A short Laravel test.',
            'duration_minutes' => 45,
            'pass_mark' => 60,
            'starts_at' => $startsAt->toDateTimeString(),
        ]);

        $test = Test::where('title', 'Laravel Basics')->firstOrFail();

        $response->assertRedirect(route('admin.tests.show', $test));
        $this->assertSame($organization->id, $test->organization_id);
        $this->assertSame($admin->id, $test->created_by_id);
        $this->assertSame(TestStatus::Draft->value, $test->status);
        $this->assertSame(
            $startsAt->toDateTimeString(),
            $test->starts_at?->toDateTimeString(),
        );
    }

    public function test_admin_can_update_test_start_time(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = Test::factory()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
        ]);
        $startsAt = now()->addDays(2)->seconds(0);

        $response = $this->actingAs($admin)->put(route('admin.tests.update', $test), [
            'title' => $test->title,
            'description' => $test->description,
            'duration_minutes' => $test->duration_minutes,
            'pass_mark' => $test->pass_mark,
            'starts_at' => $startsAt->toDateTimeString(),
        ]);

        $response->assertRedirect(route('admin.tests.show', $test));
        $this->assertSame(
            $startsAt->toDateTimeString(),
            $test->refresh()->starts_at?->toDateTimeString(),
        );
    }

    public function test_admin_without_an_organization_can_create_a_solo_test(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);

        $response = $this->actingAs($admin)->post(route('admin.tests.store'), [
            'title' => 'Solo Admin Test',
            'description' => 'Owned directly by the admin.',
            'duration_minutes' => 30,
            'pass_mark' => 10,
        ]);

        $test = Test::where('title', 'Solo Admin Test')->firstOrFail();

        $response->assertRedirect(route('admin.tests.show', $test));
        $this->assertNull($test->organization_id);
        $this->assertSame($admin->id, $test->created_by_id);
        $this->assertSame(TestStatus::Draft->value, $test->status);
    }

    public function test_admin_without_an_organization_cannot_access_another_admins_solo_test(): void
    {
        $owner = $this->userWithRole(UserRole::Admin);
        $otherAdmin = $this->userWithRole(UserRole::Admin);
        $test = Test::factory()->create([
            'organization_id' => null,
            'created_by_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherAdmin)->get(route('admin.tests.show', $test));

        $response->assertForbidden();
    }

    public function test_admin_cannot_access_another_organizations_test(): void
    {
        $adminOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $adminOrganization);
        $test = Test::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.tests.show', $test));

        $response->assertForbidden();
    }

    public function test_admin_can_publish_a_draft_test(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = Test::factory()->create([
            'organization_id' => $organization->id,
            'status' => TestStatus::Draft->value,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tests.publish', $test));

        $response->assertRedirect(route('admin.tests.show', $test));
        $this->assertSame(TestStatus::Published->value, $test->refresh()->status);
        $this->assertNotNull($test->published_at);
    }

    public function test_admin_can_close_a_published_test(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = Test::factory()->published()->create([
            'organization_id' => $organization->id,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tests.close', $test));

        $response->assertRedirect(route('admin.tests.show', $test));
        $this->assertSame(TestStatus::Closed->value, $test->refresh()->status);
        $this->assertNotNull($test->closed_at);
    }

    public function test_admin_can_edit_closed_test(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = Test::factory()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
            'title' => 'Closed Laravel Test',
            'status' => TestStatus::Closed->value,
            'published_at' => now()->subDay(),
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->put(route('admin.tests.update', $test), [
            'title' => 'Updated Closed Laravel Test',
            'description' => 'Updated for reuse.',
            'duration_minutes' => 90,
            'pass_mark' => 70,
        ]);

        $response->assertRedirect(route('admin.tests.show', $test));
        $this->assertDatabaseHas('tests', [
            'id' => $test->id,
            'title' => 'Updated Closed Laravel Test',
            'duration_minutes' => 90,
            'pass_mark' => 70,
            'status' => TestStatus::Closed->value,
        ]);
    }

    public function test_admin_can_republish_closed_test(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = Test::factory()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
            'status' => TestStatus::Closed->value,
            'published_at' => now()->subDay(),
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tests.publish', $test));

        $response->assertRedirect(route('admin.tests.show', $test));
        $this->assertSame(TestStatus::Published->value, $test->refresh()->status);
        $this->assertNull($test->closed_at);
    }

    public function test_admin_can_manage_questions_on_closed_test(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = Test::factory()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
            'status' => TestStatus::Closed->value,
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tests.questions.store', $test), [
            'body' => 'Reusable closed test MCQ?',
            'marks' => 1,
            'options' => [
                ['body' => 'Yes', 'is_correct' => true],
                ['body' => 'No', 'is_correct' => false],
            ],
        ]);

        $response->assertRedirect(route('admin.tests.questions.index', $test));
        $this->assertDatabaseHas('questions', [
            'test_id' => $test->id,
            'body' => 'Reusable closed test MCQ?',
        ]);
    }

    public function test_admin_can_delete_draft_and_closed_tests_but_not_published_tests(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $draftTest = Test::factory()->create([
            'organization_id' => $organization->id,
            'status' => TestStatus::Draft->value,
        ]);
        $closedTest = Test::factory()->create([
            'organization_id' => $organization->id,
            'status' => TestStatus::Closed->value,
            'closed_at' => now(),
        ]);
        $publishedTest = Test::factory()->published()->create([
            'organization_id' => $organization->id,
        ]);

        $draftResponse = $this->actingAs($admin)->delete(route('admin.tests.destroy', $draftTest));
        $closedResponse = $this->actingAs($admin)->delete(route('admin.tests.destroy', $closedTest));
        $publishedResponse = $this->actingAs($admin)->delete(route('admin.tests.destroy', $publishedTest));

        $draftResponse->assertRedirect(route('admin.tests.index'));
        $closedResponse->assertRedirect(route('admin.tests.index'));
        $publishedResponse->assertForbidden();
        $this->assertDatabaseMissing('tests', ['id' => $draftTest->id]);
        $this->assertDatabaseMissing('tests', ['id' => $closedTest->id]);
        $this->assertDatabaseHas('tests', ['id' => $publishedTest->id]);
    }

    public function test_deleting_a_test_removes_private_proctoring_recording_files_and_rows(): void
    {
        Storage::fake('local');

        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = Test::factory()->create([
            'organization_id' => $organization->id,
            'created_by_id' => $admin->id,
            'status' => TestStatus::Closed->value,
            'closed_at' => now(),
        ]);
        $attempt = TestAttempt::factory()->create([
            'test_id' => $test->id,
            'organization_id' => $organization->id,
        ]);
        $chunkPath = "proctoring/attempts/{$attempt->id}/recordings/camera/camera_000001.webm";
        $mergedPath = "proctoring/attempts/{$attempt->id}/recordings/camera/merged_camera.webm";
        $recording = ProctoringRecording::create([
            'test_attempt_id' => $attempt->id,
            'recording_type' => 'camera',
            'status' => 'completed',
            'chunk_count' => 1,
            'total_size_bytes' => 17,
            'merged_disk' => 'local',
            'merged_path' => $mergedPath,
            'merged_status' => 'completed',
            'merged_size_bytes' => 19,
        ]);
        $chunk = ProctoringRecordingChunk::create([
            'proctoring_recording_id' => $recording->id,
            'test_attempt_id' => $attempt->id,
            'recording_type' => 'camera',
            'disk' => 'local',
            'path' => $chunkPath,
            'mime_type' => 'video/webm',
            'size_bytes' => 17,
            'sequence' => 1,
        ]);

        Storage::disk('local')->put($chunkPath, 'fake-webm-content');
        Storage::disk('local')->put($mergedPath, 'fake-merged-content');
        Storage::disk('local')->assertExists($chunkPath);
        Storage::disk('local')->assertExists($mergedPath);

        $response = $this->actingAs($admin)
            ->delete(route('admin.tests.destroy', $test));

        $response->assertRedirect(route('admin.tests.index'));
        $this->assertDatabaseMissing('tests', ['id' => $test->id]);
        $this->assertDatabaseMissing('test_attempts', ['id' => $attempt->id]);
        $this->assertDatabaseMissing('proctoring_recordings', ['id' => $recording->id]);
        $this->assertDatabaseMissing('proctoring_recording_chunks', ['id' => $chunk->id]);
        Storage::disk('local')->assertMissing($chunkPath);
        Storage::disk('local')->assertMissing($mergedPath);
    }

    public function test_admin_can_add_an_mcq_question_with_options(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = Test::factory()->create([
            'organization_id' => $organization->id,
            'status' => TestStatus::Draft->value,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tests.questions.store', $test), [
            'body' => 'What does MVC stand for?',
            'marks' => 2,
            'options' => [
                ['body' => 'Model View Controller', 'is_correct' => true],
                ['body' => 'Module View Command', 'is_correct' => false],
            ],
        ]);

        $question = Question::where('body', 'What does MVC stand for?')->firstOrFail();

        $response->assertRedirect(route('admin.tests.questions.index', $test));
        $this->assertSame(2, $question->marks);
        $this->assertCount(2, $question->options);
        $this->assertSame(1, $question->options()->where('is_correct', true)->count());
    }

    public function test_admin_without_an_organization_can_add_an_mcq_question_to_their_solo_test(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $test = Test::factory()->create([
            'organization_id' => null,
            'created_by_id' => $admin->id,
            'status' => TestStatus::Draft->value,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tests.questions.store', $test), [
            'body' => 'Solo test MCQ?',
            'marks' => 1,
            'options' => [
                ['body' => 'Yes', 'is_correct' => true],
                ['body' => 'No', 'is_correct' => false],
            ],
        ]);

        $response->assertRedirect(route('admin.tests.questions.index', $test));
        $this->assertDatabaseHas('questions', [
            'test_id' => $test->id,
            'body' => 'Solo test MCQ?',
        ]);
    }

    public function test_mcq_validation_fails_if_fewer_than_two_options_are_provided(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = Test::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($admin)->post(route('admin.tests.questions.store', $test), [
            'body' => 'One option question',
            'marks' => 1,
            'options' => [
                ['body' => 'Only option', 'is_correct' => true],
            ],
        ]);

        $response->assertSessionHasErrors('options');
    }

    public function test_mcq_validation_fails_if_no_correct_option_is_selected(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = Test::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($admin)->post(route('admin.tests.questions.store', $test), [
            'body' => 'No correct option question',
            'marks' => 1,
            'options' => [
                ['body' => 'Option A', 'is_correct' => false],
                ['body' => 'Option B', 'is_correct' => false],
            ],
        ]);

        $response->assertSessionHasErrors('options');
    }

    public function test_mcq_validation_fails_if_multiple_correct_options_are_selected(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        $test = Test::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($admin)->post(route('admin.tests.questions.store', $test), [
            'body' => 'Multiple correct option question',
            'marks' => 1,
            'options' => [
                ['body' => 'Option A', 'is_correct' => true],
                ['body' => 'Option B', 'is_correct' => true],
            ],
        ]);

        $response->assertSessionHasErrors('options');
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
