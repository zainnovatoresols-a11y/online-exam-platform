<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Enums\InvitationStatus;
use App\Enums\TestStatus;
use App\Enums\UserRole;
use App\Models\CandidateTestDetail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\ProctoringEvent;
use App\Models\ProctoringRecording;
use App\Models\ProctoringRecordingChunk;
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

class ProctoringRecordingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_can_start_camera_recording_for_own_in_progress_attempt(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate);

        $response = $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.proctoring-recordings.start', $attempt), [
                'recording_type' => 'camera',
                'mime_type' => 'video/webm',
                'metadata' => [
                    'screen_width' => 1366,
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJson([
                'started' => true,
                'status' => 'recording',
            ]);

        $this->assertDatabaseHas('proctoring_recordings', [
            'test_attempt_id' => $attempt->id,
            'candidate_user_id' => $candidate->id,
            'recording_type' => 'camera',
            'status' => 'recording',
            'mime_type' => 'video/webm',
        ]);
        $this->assertDatabaseHas('proctoring_events', [
            'test_attempt_id' => $attempt->id,
            'event_type' => 'camera_recording_started',
            'severity' => 'low',
        ]);
    }

    public function test_candidate_can_upload_recording_chunk_for_own_in_progress_attempt(): void
    {
        Storage::fake('local');

        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.10'])
            ->withHeader('User-Agent', 'RecordingBrowser/1.0')
            ->actingAs($candidate)
            ->post(route('candidate.attempts.proctoring-recordings.chunks.store', $attempt), [
                'recording_type' => 'camera',
                'chunk' => UploadedFile::fake()->create('camera_000001.webm', 64, 'video/webm'),
                'sequence' => 1,
                'duration_ms' => 10000,
                'recorded_at' => now()->toISOString(),
                'mime_type' => 'video/webm',
                'metadata' => [
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

        $recording = ProctoringRecording::firstOrFail();
        $chunk = ProctoringRecordingChunk::firstOrFail();

        $this->assertSame('camera', $recording->recording_type);
        $this->assertSame(1, $recording->chunk_count);
        $this->assertGreaterThan(0, $recording->total_size_bytes);
        $this->assertSame('camera', $chunk->recording_type);
        $this->assertSame(1, $chunk->sequence);
        $this->assertSame('10.10.10.10', $chunk->ip_address);
        $this->assertSame('RecordingBrowser/1.0', $chunk->user_agent);
        Storage::disk('local')->assertExists($chunk->path);

        $this->assertDatabaseHas('proctoring_events', [
            'test_attempt_id' => $attempt->id,
            'event_type' => 'camera_recording_chunk_uploaded',
            'severity' => 'low',
        ]);
    }

    public function test_candidate_cannot_upload_recording_chunk_for_another_candidate_attempt(): void
    {
        Storage::fake('local');

        $candidate = $this->userWithRole(UserRole::Candidate);
        $otherCandidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate);

        $this->actingAs($otherCandidate)
            ->post(route('candidate.attempts.proctoring-recordings.chunks.store', $attempt), [
                'recording_type' => 'camera',
                'chunk' => UploadedFile::fake()->create('camera.webm', 32, 'video/webm'),
                'sequence' => 1,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('proctoring_recording_chunks', 0);
    }

    public function test_candidate_cannot_upload_recording_chunk_after_submitted(): void
    {
        Storage::fake('local');

        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate, [
            'status' => AttemptStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.proctoring-recordings.chunks.store', $attempt), [
                'recording_type' => 'camera',
                'chunk' => UploadedFile::fake()->create('camera.webm', 32, 'video/webm'),
                'sequence' => 1,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('proctoring_recording_chunks', 0);
    }

    public function test_candidate_cannot_upload_recording_chunk_after_expired(): void
    {
        Storage::fake('local');

        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate, [
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($candidate)
            ->post(route('candidate.attempts.proctoring-recordings.chunks.store', $attempt), [
                'recording_type' => 'screen',
                'chunk' => UploadedFile::fake()->create('screen.webm', 32, 'video/webm'),
                'sequence' => 1,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('proctoring_recording_chunks', 0);
    }

    public function test_public_candidate_can_start_upload_and_stop_recording_using_attempt_token(): void
    {
        Storage::fake('local');

        $admin = $this->userWithRole(UserRole::Admin);
        $test = $this->publishedTestFor($admin);
        [$invitation, $attempt] = $this->publicAttemptFor($test, $admin);

        $this->postJson(route('candidate.public-attempts.proctoring-recordings.start', $invitation->token), [
            'recording_type' => 'screen',
            'mime_type' => 'video/webm',
        ])->assertCreated();

        $this->post(route('candidate.public-attempts.proctoring-recordings.chunks.store', $invitation->token), [
            'recording_type' => 'screen',
            'chunk' => UploadedFile::fake()->create('screen_000001.webm', 64, 'video/webm'),
            'sequence' => 1,
            'duration_ms' => 10000,
            'mime_type' => 'video/webm',
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $this->postJson(route('candidate.public-attempts.proctoring-recordings.stop', $invitation->token), [
            'recording_type' => 'screen',
            'reason' => 'screen_share_ended',
        ])->assertOk();

        $this->assertDatabaseHas('proctoring_recordings', [
            'test_attempt_id' => $attempt->id,
            'candidate_user_id' => null,
            'recording_type' => 'screen',
            'status' => 'stopped',
        ]);
        $this->assertDatabaseHas('proctoring_events', [
            'test_attempt_id' => $attempt->id,
            'event_type' => 'screen_share_ended',
            'severity' => 'high',
        ]);
    }

    public function test_invalid_recording_chunk_upload_is_rejected(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate);

        $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.proctoring-recordings.chunks.store', $attempt), [
                'recording_type' => 'camera',
                'chunk' => 'not-a-file',
                'sequence' => 1,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('proctoring_recording_chunks', 0);
    }

    public function test_recording_permission_events_are_accepted_with_server_side_severity(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate);

        foreach ([
            'camera_recording_permission_denied' => 'high',
            'screen_recording_permission_denied' => 'high',
            'camera_recording_error' => 'medium',
            'screen_recording_chunk_failed' => 'medium',
        ] as $eventType => $severity) {
            $this->actingAs($candidate)
                ->postJson(route('candidate.attempts.proctoring-events.store', $attempt), [
                    'event_type' => $eventType,
                    'metadata' => [
                        'source' => 'recording_test',
                    ],
                ])
                ->assertCreated();

            $this->assertDatabaseHas('proctoring_events', [
                'test_attempt_id' => $attempt->id,
                'event_type' => $eventType,
                'severity' => $severity,
            ]);
        }
    }

    public function test_admin_can_view_recording_chunk_for_in_scope_attempt(): void
    {
        Storage::fake('local');

        $admin = $this->userWithRole(UserRole::Admin);
        $test = $this->publishedTestFor($admin);
        [, $attempt] = $this->publicAttemptFor($test, $admin, [
            'status' => AttemptStatus::Submitted,
            'submitted_at' => now(),
        ]);
        $chunk = $this->recordingChunkFor($attempt, 'camera');

        Storage::disk('local')->put($chunk->path, 'fake-video-content');

        $this->actingAs($admin)
            ->get(route('admin.proctoring-recording-chunks.show', $chunk))
            ->assertOk();
    }

    public function test_admin_cannot_view_recording_chunk_outside_scope(): void
    {
        Storage::fake('local');

        $owner = $this->userWithRole(UserRole::Admin);
        $otherAdmin = $this->userWithRole(UserRole::Admin);
        $test = $this->publishedTestFor($owner);
        [, $attempt] = $this->publicAttemptFor($test, $owner, [
            'status' => AttemptStatus::Submitted,
            'submitted_at' => now(),
        ]);
        $chunk = $this->recordingChunkFor($attempt, 'screen');

        Storage::disk('local')->put($chunk->path, 'fake-video-content');

        $this->actingAs($otherAdmin)
            ->get(route('admin.proctoring-recording-chunks.show', $chunk))
            ->assertForbidden();
    }

    public function test_candidate_cannot_view_admin_recording_chunk_route(): void
    {
        Storage::fake('local');

        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate, [
            'status' => AttemptStatus::Submitted,
            'submitted_at' => now(),
        ]);
        $chunk = $this->recordingChunkFor($attempt, 'camera');

        Storage::disk('local')->put($chunk->path, 'fake-video-content');

        $this->actingAs($candidate)
            ->get(route('admin.proctoring-recording-chunks.show', $chunk))
            ->assertForbidden();
    }

    public function test_admin_result_page_includes_recording_summary_and_paginated_chunks(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $test = $this->publishedTestFor($admin);
        [, $attempt] = $this->publicAttemptFor($test, $admin, [
            'status' => AttemptStatus::Submitted,
            'submitted_at' => now(),
        ]);

        for ($index = 1; $index <= 14; $index++) {
            $this->recordingChunkFor($attempt, $index % 2 === 0 ? 'screen' : 'camera', $index);
        }

        $response = $this->actingAs($admin)
            ->get(route('admin.tests.results.show', [$test, $attempt]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Results/Show')
            ->where('proctoring_recording_summary.camera_status', 'recording')
            ->where('proctoring_recording_summary.camera_chunk_count', 7)
            ->where('proctoring_recording_summary.screen_chunk_count', 7)
            ->where('proctoring_camera_recording_chunks.per_page', 12)
            ->where('proctoring_camera_recording_chunks.total', 7)
            ->has('proctoring_camera_recording_chunks.data', 7)
            ->where('proctoring_camera_recording_chunks.data.0.recording_type', 'camera')
            ->where('proctoring_camera_recording_chunks.data.0.sequence', 1)
            ->where(
                'proctoring_camera_recording_chunks.data.0.url',
                route('admin.proctoring-recording-chunks.show', ProctoringRecordingChunk::query()->where('recording_type', 'camera')->orderBy('sequence')->first()),
            )
            ->where('proctoring_screen_recording_chunks.per_page', 12)
            ->where('proctoring_screen_recording_chunks.total', 7)
            ->has('proctoring_screen_recording_chunks.data', 7)
            ->where('proctoring_screen_recording_chunks.data.0.recording_type', 'screen')
            ->where('proctoring_screen_recording_chunks.data.0.sequence', 2));
    }

    public function test_candidate_result_page_does_not_expose_recordings(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate, [
            'status' => AttemptStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $chunk = $this->recordingChunkFor($attempt, 'camera');

        $response = $this->actingAs($candidate)
            ->get(route('candidate.attempts.show', $attempt));

        $response->assertOk()
            ->assertDontSee($chunk->path);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Attempts/Result')
            ->missing('proctoring_recording_summary')
            ->missing('proctoring_recording_chunks')
            ->missing('proctoring_camera_recording_chunks')
            ->missing('proctoring_screen_recording_chunks'));
    }

    private function recordingChunkFor(TestAttempt $attempt, string $type, int $sequence = 1): ProctoringRecordingChunk
    {
        $recording = ProctoringRecording::query()->firstOrCreate([
            'test_attempt_id' => $attempt->id,
            'recording_type' => $type,
        ], [
            'candidate_user_id' => $attempt->candidate_user_id,
            'status' => 'recording',
            'started_at' => now()->subMinutes(5),
            'chunk_count' => 0,
            'total_size_bytes' => 0,
            'mime_type' => 'video/webm',
        ]);
        $event = ProctoringEvent::create([
            'test_attempt_id' => $attempt->id,
            'candidate_user_id' => $attempt->candidate_user_id,
            'event_type' => "{$type}_recording_chunk_uploaded",
            'severity' => 'low',
            'occurred_at' => now(),
            'metadata' => [
                'sequence' => $sequence,
            ],
        ]);
        $chunk = $recording->chunks()->create([
            'test_attempt_id' => $attempt->id,
            'candidate_user_id' => $attempt->candidate_user_id,
            'proctoring_event_id' => $event->id,
            'recording_type' => $type,
            'disk' => 'local',
            'path' => "proctoring/attempts/{$attempt->id}/recordings/{$type}/{$type}_".str_pad((string) $sequence, 6, '0', STR_PAD_LEFT).'.webm',
            'mime_type' => 'video/webm',
            'size_bytes' => 1024,
            'sequence' => $sequence,
            'duration_ms' => 10000,
            'recorded_at' => now()->subMinutes(5)->addSeconds($sequence * 10),
            'uploaded_at' => now()->subMinutes(5)->addSeconds($sequence * 10),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'RecordingBrowser/1.0',
            'metadata' => [
                'fullscreen' => true,
            ],
        ]);

        $recording->forceFill([
            'chunk_count' => $recording->chunks()->count(),
            'total_size_bytes' => $recording->chunks()->sum('size_bytes'),
            'last_chunk_at' => $recording->chunks()->max('uploaded_at'),
        ])->save();

        return $chunk;
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
