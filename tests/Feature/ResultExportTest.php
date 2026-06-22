<?php

namespace Tests\Feature;

use App\Actions\Results\BuildAttemptResultExportData;
use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\AttemptAnswer;
use App\Models\AttemptProctoringReview;
use App\Models\CandidateTestDetail;
use App\Models\CodeExecutionRun;
use App\Models\CodeExecutionTestCaseResult;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\ProctoringEvent;
use App\Models\ProctoringRecording;
use App\Models\ProctoringRecordingChunk;
use App\Models\Question;
use App\Models\QuestionTestCase;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ResultExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_export_test_results_csv(): void
    {
        $superAdmin = $this->userWithRole(UserRole::SuperAdmin);
        $admin = $this->userWithRole(UserRole::Admin);
        [$test] = $this->resultFixture($admin);

        $response = $this->actingAs($superAdmin)
            ->get(route('admin.tests.results.export.csv', $test));

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $this->assertStringContainsString('Candidate Name', $response->streamedContent());
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.csv', (string) $response->headers->get('content-disposition'));
    }

    public function test_organization_admin_can_export_csv_for_organization_test(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        [$test] = $this->resultFixture($admin, $organization);

        $this->actingAs($admin)
            ->get(route('admin.tests.results.export.csv', $test))
            ->assertOk();
    }

    public function test_solo_admin_can_export_csv_for_own_solo_test(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test] = $this->resultFixture($admin);

        $this->actingAs($admin)
            ->get(route('admin.tests.results.export.csv', $test))
            ->assertOk();
    }

    public function test_admin_cannot_export_csv_outside_scope(): void
    {
        $owner = $this->userWithRole(UserRole::Admin);
        $otherAdmin = $this->userWithRole(UserRole::Admin);
        [$test] = $this->resultFixture($owner);

        $this->actingAs($otherAdmin)
            ->get(route('admin.tests.results.export.csv', $test))
            ->assertForbidden();
    }

    public function test_candidate_cannot_export_csv(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $candidate = $this->userWithRole(UserRole::Candidate);
        [$test] = $this->resultFixture($admin, null, $candidate);

        $this->actingAs($candidate)
            ->get(route('admin.tests.results.export.csv', $test))
            ->assertForbidden();
    }

    public function test_csv_includes_expected_headers(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test] = $this->resultFixture($admin);

        $content = $this->actingAs($admin)
            ->get(route('admin.tests.results.export.csv', $test))
            ->streamedContent();

        foreach ([
            'Candidate Name',
            'Candidate Email',
            'Proctoring Review Status',
            'Proctoring Risk Level',
            'Reviewed By',
        ] as $header) {
            $this->assertStringContainsString($header, $content);
        }
    }

    public function test_csv_includes_proctoring_review_status_and_risk_level(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test] = $this->resultFixture($admin);

        $content = $this->actingAs($admin)
            ->get(route('admin.tests.results.export.csv', $test))
            ->streamedContent();

        $this->assertStringContainsString('flagged', $content);
        $this->assertStringContainsString('high', $content);
        $this->assertStringContainsString($admin->name, $content);
    }

    public function test_csv_does_not_include_private_recording_paths(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->resultFixture($admin);

        $recording = ProctoringRecording::create([
            'test_attempt_id' => $attempt->id,
            'candidate_user_id' => $attempt->candidate_user_id,
            'recording_type' => 'camera',
            'status' => 'completed',
            'chunk_count' => 1,
            'total_size_bytes' => 512,
        ]);

        ProctoringRecordingChunk::create([
            'proctoring_recording_id' => $recording->id,
            'test_attempt_id' => $attempt->id,
            'candidate_user_id' => $attempt->candidate_user_id,
            'recording_type' => 'camera',
            'disk' => 'local',
            'path' => 'proctoring/attempts/private-secret-camera.webm',
            'mime_type' => 'video/webm',
            'size_bytes' => 512,
            'sequence' => 1,
            'uploaded_at' => now(),
        ]);

        $content = $this->actingAs($admin)
            ->get(route('admin.tests.results.export.csv', $test))
            ->streamedContent();

        $this->assertStringNotContainsString('private-secret-camera.webm', $content);
        $this->assertStringNotContainsString('proctoring/attempts', $content);
    }

    public function test_super_admin_can_export_attempt_pdf(): void
    {
        $superAdmin = $this->userWithRole(UserRole::SuperAdmin);
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->resultFixture($admin);

        $this->actingAs($superAdmin)
            ->get(route('admin.tests.results.attempts.export.pdf', [$test, $attempt]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition');
    }

    public function test_organization_admin_can_export_attempt_pdf_for_organization_test(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->userWithRole(UserRole::Admin, $organization);
        [$test, $attempt] = $this->resultFixture($admin, $organization);

        $this->actingAs($admin)
            ->get(route('admin.tests.results.attempts.export.pdf', [$test, $attempt]))
            ->assertOk();
    }

    public function test_solo_admin_can_export_attempt_pdf_for_own_solo_test(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->resultFixture($admin);

        $this->actingAs($admin)
            ->get(route('admin.tests.results.attempts.export.pdf', [$test, $attempt]))
            ->assertOk();
    }

    public function test_admin_cannot_export_attempt_pdf_outside_scope(): void
    {
        $owner = $this->userWithRole(UserRole::Admin);
        $otherAdmin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->resultFixture($owner);

        $this->actingAs($otherAdmin)
            ->get(route('admin.tests.results.attempts.export.pdf', [$test, $attempt]))
            ->assertForbidden();
    }

    public function test_candidate_cannot_export_attempt_pdf(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $candidate = $this->userWithRole(UserRole::Candidate);
        [$test, $attempt] = $this->resultFixture($admin, null, $candidate);

        $this->actingAs($candidate)
            ->get(route('admin.tests.results.attempts.export.pdf', [$test, $attempt]))
            ->assertForbidden();
    }

    public function test_pdf_response_has_application_pdf_content_type(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->resultFixture($admin);

        $this->actingAs($admin)
            ->get(route('admin.tests.results.attempts.export.pdf', [$test, $attempt]))
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_pdf_export_does_not_expose_hidden_test_case_raw_input_or_output(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->resultFixture($admin);

        $content = $this->actingAs($admin)
            ->get(route('admin.tests.results.attempts.export.pdf', [$test, $attempt]))
            ->getContent();
        $payload = json_encode(app(BuildAttemptResultExportData::class)($test, $attempt));

        $this->assertStringNotContainsString('hidden-admin-input', $content);
        $this->assertStringNotContainsString('hidden-admin-output', $content);
        $this->assertStringNotContainsString('hidden-admin-input', (string) $payload);
        $this->assertStringNotContainsString('hidden-admin-output', (string) $payload);
    }

    public function test_attempt_must_belong_to_given_test_before_pdf_export_works(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        [$firstTest, $attempt] = $this->resultFixture($admin);
        $secondTest = Test::factory()->published()->create([
            'organization_id' => null,
            'created_by_id' => $admin->id,
        ]);

        $this->assertNotSame($firstTest->id, $secondTest->id);

        $this->actingAs($admin)
            ->get(route('admin.tests.results.attempts.export.pdf', [$secondTest, $attempt]))
            ->assertNotFound();
    }

    public function test_export_buttons_appear_on_admin_result_pages(): void
    {
        $indexPage = file_get_contents(resource_path('js/Pages/Admin/Results/Index.tsx'));
        $showPage = file_get_contents(resource_path('js/Pages/Admin/Results/Show.tsx'));

        $this->assertStringContainsString('Export CSV', (string) $indexPage);
        $this->assertStringContainsString('admin.tests.results.export.csv', (string) $indexPage);
        $this->assertStringContainsString('download', (string) $indexPage);
        $this->assertStringContainsString('Export PDF', (string) $showPage);
        $this->assertStringContainsString('admin.tests.results.attempts.export.pdf', (string) $showPage);
        $this->assertStringContainsString('download', (string) $showPage);
    }

    public function test_candidate_result_page_does_not_expose_export_links(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        $admin = $this->userWithRole(UserRole::Admin);
        [$test, $attempt] = $this->resultFixture($admin, null, $candidate);

        $this->actingAs($candidate)
            ->get(route('candidate.attempts.show', $attempt))
            ->assertOk()
            ->assertDontSee('Export PDF')
            ->assertDontSee('Export CSV');
    }

    /**
     * @return array{0: Test, 1: TestAttempt}
     */
    private function resultFixture(
        User $admin,
        ?Organization $organization = null,
        ?User $candidate = null,
    ): array {
        $candidate ??= User::factory()->create([
            'name' => 'Export Candidate',
            'email' => 'export-candidate@example.com',
            'phone' => '555-123',
            'stack_name' => 'Laravel',
        ]);

        $test = Test::factory()->published()->create([
            'organization_id' => $organization?->id,
            'created_by_id' => $admin->id,
            'title' => 'Export Test',
            'duration_minutes' => 45,
            'pass_mark' => 60,
            'candidate_fields' => ['phone', 'stack_name'],
        ]);

        $invitation = Invitation::factory()->create([
            'organization_id' => $organization?->id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => $candidate->id,
            'name' => 'Export Candidate',
            'email' => 'export-candidate@example.com',
            'candidate_profile' => [
                'phone' => '555-123',
                'stack_name' => 'Laravel',
            ],
        ]);

        $attempt = TestAttempt::factory()->create([
            'test_id' => $test->id,
            'invitation_id' => $invitation->id,
            'candidate_user_id' => $candidate->id,
            'organization_id' => $organization?->id,
            'status' => AttemptStatus::Submitted,
            'started_at' => now()->subHour(),
            'submitted_at' => now()->subMinutes(10),
            'expires_at' => now()->addMinutes(10),
            'score' => 15,
            'max_score' => 20,
            'total_marks' => 20,
            'percentage' => 75,
            'passed' => true,
        ]);

        CandidateTestDetail::create([
            'organization_id' => $organization?->id,
            'test_id' => $test->id,
            'invitation_id' => $invitation->id,
            'test_attempt_id' => $attempt->id,
            'name' => 'Export Candidate',
            'email' => 'export-candidate@example.com',
            'phone' => '555-123',
            'stack_name' => 'Laravel',
            'fields' => [
                'phone' => '555-123',
                'stack_name' => 'Laravel',
            ],
            'submitted_at' => now()->subMinutes(55),
        ]);

        $mcqQuestion = Question::factory()->create([
            'test_id' => $test->id,
            'type' => QuestionType::Mcq->value,
            'body' => 'What is Laravel?',
            'marks' => 5,
            'order' => 1,
        ]);

        $codingQuestion = Question::factory()->create([
            'test_id' => $test->id,
            'type' => QuestionType::Coding->value,
            'body' => 'Reverse a string.',
            'marks' => 15,
            'order' => 2,
        ]);

        AttemptAnswer::create([
            'test_attempt_id' => $attempt->id,
            'question_id' => $mcqQuestion->id,
            'is_correct' => true,
            'score' => 5,
            'answered_at' => now()->subMinutes(30),
        ]);

        $codingAnswer = AttemptAnswer::create([
            'test_attempt_id' => $attempt->id,
            'question_id' => $codingQuestion->id,
            'language' => 'php',
            'submitted_code' => 'echo "secret submitted code";',
            'is_correct' => true,
            'score' => 10,
            'answered_at' => now()->subMinutes(20),
        ]);

        $run = CodeExecutionRun::create([
            'test_attempt_id' => $attempt->id,
            'question_id' => $codingQuestion->id,
            'attempt_answer_id' => $codingAnswer->id,
            'candidate_user_id' => $candidate->id,
            'language' => 'php',
            'status' => 'accepted',
            'run_type' => 'final',
            'source_code' => 'echo "secret source code";',
            'score_awarded' => 10,
            'max_score' => 15,
            'passed' => true,
            'started_at' => now()->subMinutes(18),
            'finished_at' => now()->subMinutes(17),
        ]);

        $visibleCase = QuestionTestCase::create([
            'question_id' => $codingQuestion->id,
            'input' => 'abc',
            'expected_output' => 'cba',
            'is_hidden' => false,
            'sort_order' => 1,
            'points' => 5,
        ]);
        $hiddenCase = QuestionTestCase::create([
            'question_id' => $codingQuestion->id,
            'input' => 'hidden-admin-input',
            'expected_output' => 'hidden-admin-output',
            'is_hidden' => true,
            'sort_order' => 2,
            'points' => 10,
        ]);

        CodeExecutionTestCaseResult::create([
            'code_execution_run_id' => $run->id,
            'question_test_case_id' => $visibleCase->id,
            'is_hidden' => false,
            'status' => 'passed',
            'passed' => true,
            'input' => 'abc',
            'expected_output' => 'cba',
            'actual_output' => 'cba',
        ]);
        CodeExecutionTestCaseResult::create([
            'code_execution_run_id' => $run->id,
            'question_test_case_id' => $hiddenCase->id,
            'is_hidden' => true,
            'status' => 'passed',
            'passed' => true,
            'input' => 'hidden-admin-input',
            'expected_output' => 'hidden-admin-output',
            'actual_output' => 'hidden-admin-output',
        ]);

        foreach ([
            ['event_type' => 'tab_hidden', 'severity' => 'medium'],
            ['event_type' => 'fullscreen_exited', 'severity' => 'high'],
            ['event_type' => 'copy_attempt', 'severity' => 'high'],
            ['event_type' => 'camera_recording_permission_denied', 'severity' => 'high'],
            ['event_type' => 'screen_share_ended', 'severity' => 'high'],
        ] as $event) {
            ProctoringEvent::create([
                'test_attempt_id' => $attempt->id,
                'candidate_user_id' => $candidate->id,
                'event_type' => $event['event_type'],
                'severity' => $event['severity'],
                'occurred_at' => now()->subMinutes(15),
            ]);
        }

        AttemptProctoringReview::create([
            'test_attempt_id' => $attempt->id,
            'test_id' => $test->id,
            'organization_id' => $organization?->id,
            'reviewed_by_user_id' => $admin->id,
            'status' => 'flagged',
            'risk_level' => 'high',
            'reason_codes' => ['tab_switching'],
            'notes' => 'Reviewed for export.',
            'reviewed_at' => now()->subMinutes(5),
        ]);

        return [$test, $attempt];
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
