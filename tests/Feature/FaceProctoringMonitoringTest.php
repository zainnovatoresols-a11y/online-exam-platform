<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Enums\InvitationStatus;
use App\Enums\TestStatus;
use App\Enums\UserRole;
use App\Models\CandidateTestDetail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\ProctoringFaceSnapshot;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FaceProctoringMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_can_upload_face_violation_for_own_in_progress_attempt(): void
    {
        Storage::fake('local');

        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])
            ->withHeader('User-Agent', 'FaceBrowser/1.0')
            ->actingAs($candidate)
            ->post(route('candidate.attempts.face-proctoring-violations.store', $attempt), [
                'violation_type' => 'no_face',
                'face_count' => 0,
                'snapshot' => UploadedFile::fake()->image('no-face.jpg', 640, 360),
                'captured_at' => now()->toISOString(),
                'started_at' => now()->subSeconds(5)->toISOString(),
                'duration_seconds' => 5,
                'metadata' => [
                    'video_width' => 640,
                    'fullscreen' => true,
                ],
            ], [
                'Accept' => 'application/json',
            ]);

        $response
            ->assertCreated()
            ->assertJson([
                'stored' => true,
            ]);

        $snapshot = ProctoringFaceSnapshot::firstOrFail();

        $this->assertSame($attempt->id, $snapshot->test_attempt_id);
        $this->assertSame($candidate->id, $snapshot->candidate_user_id);
        $this->assertSame('no_face', $snapshot->violation_type);
        $this->assertSame(0, $snapshot->face_count);
        $this->assertSame(5, $snapshot->duration_seconds);
        $this->assertSame('10.20.30.40', $snapshot->ip_address);
        $this->assertSame('FaceBrowser/1.0', $snapshot->user_agent);
        Storage::disk('local')->assertExists($snapshot->path);

        $this->assertDatabaseHas('proctoring_events', [
            'id' => $snapshot->proctoring_event_id,
            'test_attempt_id' => $attempt->id,
            'candidate_user_id' => $candidate->id,
            'event_type' => 'face_no_face_detected',
            'severity' => 'high',
        ]);
    }

    public function test_candidate_can_update_no_face_duration_when_face_returns(): void
    {
        Storage::fake('local');

        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate);
        $snapshot = $this->storeSnapshot($attempt, 'no_face', 0);

        $this->actingAs($candidate)
            ->patchJson(route('candidate.attempts.face-proctoring-violations.duration.update', [$attempt, $snapshot]), [
                'ended_at' => now()->toISOString(),
                'duration_seconds' => 17,
                'metadata' => [
                    'visibility_state' => 'visible',
                ],
            ])
            ->assertOk()
            ->assertJson([
                'updated' => true,
                'duration_seconds' => 17,
            ]);

        $snapshot->refresh();

        $this->assertSame(17, $snapshot->duration_seconds);
        $this->assertNotNull($snapshot->ended_at);
        $this->assertSame('visible', $snapshot->metadata['visibility_state']);
        $this->assertSame(17, $snapshot->event->refresh()->metadata['duration_seconds']);
    }

    public function test_public_candidate_can_upload_face_violation_using_attempt_token(): void
    {
        Storage::fake('local');

        $admin = $this->userWithRole(UserRole::Admin);
        $test = $this->publishedTestFor($admin);
        [$invitation, $attempt] = $this->publicAttemptFor($test, $admin);

        $this->post(route('candidate.public-attempts.face-proctoring-violations.store', $invitation->token), [
            'violation_type' => 'multiple_faces',
            'face_count' => 2,
            'snapshot' => UploadedFile::fake()->image('multiple-faces.jpg', 640, 360),
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $this->assertDatabaseHas('proctoring_face_snapshots', [
            'test_attempt_id' => $attempt->id,
            'candidate_user_id' => null,
            'violation_type' => 'multiple_faces',
            'face_count' => 2,
        ]);
        $this->assertDatabaseHas('proctoring_events', [
            'test_attempt_id' => $attempt->id,
            'candidate_user_id' => null,
            'event_type' => 'face_multiple_faces_detected',
            'severity' => 'high',
        ]);
    }

    public function test_candidate_cannot_upload_face_violation_for_another_candidate_attempt(): void
    {
        Storage::fake('local');

        $candidate = $this->userWithRole(UserRole::Candidate);
        $otherCandidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate);

        $this->actingAs($otherCandidate)
            ->post(route('candidate.attempts.face-proctoring-violations.store', $attempt), [
                'violation_type' => 'no_face',
                'face_count' => 0,
                'snapshot' => UploadedFile::fake()->image('no-face.jpg', 640, 360),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('proctoring_face_snapshots', 0);
    }

    public function test_face_violation_upload_is_rejected_after_attempt_is_submitted(): void
    {
        Storage::fake('local');

        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate, [
            'status' => AttemptStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.face-proctoring-violations.store', $attempt), [
                'violation_type' => 'no_face',
                'face_count' => 0,
                'snapshot' => UploadedFile::fake()->image('no-face.jpg', 640, 360),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('proctoring_face_snapshots', 0);
    }

    public function test_invalid_face_snapshot_upload_is_rejected(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate);

        $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.face-proctoring-violations.store', $attempt), [
                'violation_type' => 'no_face',
                'face_count' => 0,
                'snapshot' => 'not-an-image',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('proctoring_face_snapshots', 0);
    }

    public function test_admin_can_view_face_snapshot_image_for_in_scope_attempt(): void
    {
        Storage::fake('local');

        $admin = $this->userWithRole(UserRole::Admin);
        $test = $this->publishedTestFor($admin);
        [, $attempt] = $this->publicAttemptFor($test, $admin);
        $snapshot = $this->storeSnapshot($attempt, 'no_face', 0);

        $this->actingAs($admin)
            ->get(route('admin.proctoring-face-snapshots.show', $snapshot))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
    }

    public function test_candidate_cannot_view_admin_face_snapshot_route(): void
    {
        Storage::fake('local');

        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate);
        $snapshot = $this->storeSnapshot($attempt, 'no_face', 0);

        $this->actingAs($candidate)
            ->get(route('admin.proctoring-face-snapshots.show', $snapshot))
            ->assertForbidden();
    }

    public function test_admin_result_page_includes_face_violation_counts_and_snapshots(): void
    {
        Storage::fake('local');

        $admin = $this->userWithRole(UserRole::Admin);
        $test = $this->publishedTestFor($admin);
        [, $attempt] = $this->publicAttemptFor($test, $admin, [
            'status' => AttemptStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $this->storeSnapshot($attempt, 'no_face', 0);
        $this->storeSnapshot($attempt, 'multiple_faces', 2);

        $response = $this->actingAs($admin)
            ->get(route('admin.tests.results.show', [$test, $attempt]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Results/Show')
            ->where('proctoring_summary.no_face_violations', 1)
            ->where('proctoring_summary.multiple_face_violations', 1)
            ->where('face_proctoring_summary.total', 2)
            ->where('face_proctoring_summary.no_face', 1)
            ->where('face_proctoring_summary.multiple_faces', 1)
            ->where('face_proctoring_summary.no_face_duration_seconds', 12)
            ->where('face_proctoring_snapshots.total', 2)
            ->has('face_proctoring_snapshots.data', 2)
            ->where('face_proctoring_snapshots.data.1.duration_seconds', 12)
            ->where('face_proctoring_snapshots.data.0.url', fn (string $url): bool => str_contains($url, '/admin/proctoring-face-snapshots/')));
    }

    public function test_candidate_result_page_does_not_expose_face_snapshots(): void
    {
        Storage::fake('local');

        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate, [
            'status' => AttemptStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $this->storeSnapshot($attempt, 'no_face', 0);

        $response = $this->actingAs($candidate)
            ->get(route('candidate.attempts.show', $attempt));

        $response->assertOk()
            ->assertDontSee('face-violations');
        $response->assertInertia(fn (Assert $page) => $page
            ->missing('face_proctoring_summary')
            ->missing('face_proctoring_snapshots'));
    }

    private function storeSnapshot(TestAttempt $attempt, string $violationType, int $faceCount): ProctoringFaceSnapshot
    {
        $path = "proctoring/attempts/{$attempt->id}/face-violations/test-{$violationType}.jpg";
        Storage::disk('local')->put($path, 'fake-image-content');

        $event = $attempt->proctoringEvents()->create([
            'candidate_user_id' => $attempt->candidate_user_id,
            'event_type' => $violationType === 'no_face'
                ? 'face_no_face_detected'
                : 'face_multiple_faces_detected',
            'severity' => 'high',
            'occurred_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'FaceBrowser/1.0',
            'metadata' => [
                'violation_type' => $violationType,
                'face_count' => $faceCount,
            ],
        ]);

        return $attempt->proctoringFaceSnapshots()->create([
            'candidate_user_id' => $attempt->candidate_user_id,
            'proctoring_event_id' => $event->id,
            'violation_type' => $violationType,
            'face_count' => $faceCount,
            'disk' => 'local',
            'path' => $path,
            'mime_type' => 'image/jpeg',
            'size_bytes' => 18,
            'captured_at' => now(),
            'started_at' => now()->subSeconds(12),
            'ended_at' => $violationType === 'no_face' ? now() : null,
            'duration_seconds' => $violationType === 'no_face' ? 12 : 0,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'FaceBrowser/1.0',
            'metadata' => [
                'video_width' => 640,
            ],
        ]);
    }

    /**
     * @return array{0: Test, 1: TestAttempt}
     */
    private function attemptForCandidate(User $candidate, array $attemptOverrides = []): array
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $test = $this->publishedTestFor($admin);

        $attempt = TestAttempt::factory()->create(array_merge([
            'test_id' => $test->id,
            'candidate_user_id' => $candidate->id,
            'organization_id' => null,
            'status' => AttemptStatus::InProgress,
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(55),
        ], $attemptOverrides));

        return [$test, $attempt];
    }

    /**
     * @return array{0: Invitation, 1: TestAttempt}
     */
    private function publicAttemptFor(Test $test, User $admin, array $attemptOverrides = []): array
    {
        $startedAt = now()->subMinutes(5);

        $invitation = Invitation::factory()->create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invited_by' => $admin->id,
            'candidate_user_id' => null,
            'name' => 'Public Candidate',
            'email' => 'public-candidate@example.com',
            'status' => InvitationStatus::Accepted,
            'accepted_at' => $startedAt,
            'policy_accepted_at' => $startedAt,
        ]);

        $attempt = TestAttempt::factory()->create(array_merge([
            'test_id' => $test->id,
            'invitation_id' => $invitation->id,
            'candidate_user_id' => null,
            'organization_id' => $test->organization_id,
            'status' => AttemptStatus::InProgress,
            'started_at' => $startedAt,
            'expires_at' => $startedAt->copy()->addMinutes((int) $test->duration_minutes),
        ], $attemptOverrides));

        CandidateTestDetail::create([
            'organization_id' => $test->organization_id,
            'test_id' => $test->id,
            'invitation_id' => $invitation->id,
            'test_attempt_id' => $attempt->id,
            'name' => 'Public Candidate',
            'email' => 'public-candidate@example.com',
            'phone' => '03001234567',
            'stack_name' => 'Laravel',
            'fields' => [
                'name' => 'Public Candidate',
                'email' => 'public-candidate@example.com',
            ],
            'submitted_at' => $startedAt,
        ]);

        return [$invitation, $attempt];
    }

    private function publishedTestFor(User $admin, ?Organization $organization = null): Test
    {
        return Test::factory()->create([
            'organization_id' => $organization?->id,
            'created_by_id' => $admin->id,
            'title' => 'Backend Developer Assessment',
            'duration_minutes' => 60,
            'pass_mark' => 60,
            'status' => TestStatus::Published->value,
            'published_at' => now(),
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
