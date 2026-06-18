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
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProctoringEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_candidate_can_record_proctoring_event_for_own_in_progress_attempt(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate);

        $response = $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.proctoring-events.store', $attempt), [
                'event_type' => 'tab_hidden',
                'occurred_at' => now()->toISOString(),
                'metadata' => [
                    'visibility_state' => 'hidden',
                    'fullscreen' => false,
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJson([
                'recorded' => true,
                'duplicate' => false,
            ]);

        $this->assertDatabaseHas('proctoring_events', [
            'test_attempt_id' => $attempt->id,
            'candidate_user_id' => $candidate->id,
            'event_type' => 'tab_hidden',
            'severity' => 'medium',
        ]);
    }

    public function test_candidate_cannot_record_event_for_another_candidate_attempt(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        $otherCandidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate);

        $this->actingAs($otherCandidate)
            ->postJson(route('candidate.attempts.proctoring-events.store', $attempt), [
                'event_type' => 'window_blur',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('proctoring_events', 0);
    }

    public function test_candidate_cannot_record_event_after_attempt_is_submitted(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate, [
            'status' => AttemptStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.proctoring-events.store', $attempt), [
                'event_type' => 'window_blur',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('proctoring_events', 0);
    }

    public function test_candidate_cannot_record_event_after_attempt_is_expired(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate, [
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.proctoring-events.store', $attempt), [
                'event_type' => 'window_blur',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('proctoring_events', 0);
    }

    public function test_public_candidate_can_record_proctoring_event_using_attempt_token(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $test = $this->publishedTestFor($admin);
        [$invitation, $attempt] = $this->publicAttemptFor($test, $admin);

        $response = $this->postJson(route('candidate.public-attempts.proctoring-events.store', $invitation->token), [
            'event_type' => 'right_click_attempt',
            'metadata' => [
                'button' => 2,
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJson([
                'recorded' => true,
                'duplicate' => false,
            ]);

        $this->assertDatabaseHas('proctoring_events', [
            'test_attempt_id' => $attempt->id,
            'candidate_user_id' => null,
            'event_type' => 'right_click_attempt',
            'severity' => 'medium',
        ]);
    }

    public function test_invalid_event_type_is_rejected(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate);

        $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.proctoring-events.store', $attempt), [
                'event_type' => 'camera_started',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('proctoring_events', 0);
    }

    public function test_event_stores_server_side_ip_and_user_agent(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate);

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])
            ->withHeader('User-Agent', 'FeatureBrowser/1.0')
            ->actingAs($candidate)
            ->postJson(route('candidate.attempts.proctoring-events.store', $attempt), [
                'event_type' => 'tab_hidden',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('proctoring_events', [
            'test_attempt_id' => $attempt->id,
            'ip_address' => '10.20.30.40',
            'user_agent' => 'FeatureBrowser/1.0',
        ]);
    }

    public function test_severity_is_assigned_server_side(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate);

        $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.proctoring-events.store', $attempt), [
                'event_type' => 'copy_attempt',
                'severity' => 'low',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('proctoring_events', [
            'test_attempt_id' => $attempt->id,
            'event_type' => 'copy_attempt',
            'severity' => 'high',
        ]);
    }

    public function test_blocking_control_events_are_accepted_with_server_side_severity(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate);

        $events = [
            'drag_attempt' => 'medium',
            'drop_attempt' => 'medium',
            'proctoring_violation_acknowledged' => 'low',
        ];

        foreach ($events as $eventType => $severity) {
            $this->actingAs($candidate)
                ->postJson(route('candidate.attempts.proctoring-events.store', $attempt), [
                    'event_type' => $eventType,
                    'metadata' => [
                        'source' => 'blocking_controls_test',
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

    public function test_duplicate_noisy_event_within_three_seconds_is_ignored(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate);
        $payload = [
            'event_type' => 'window_blur',
            'metadata' => [
                'fullscreen' => false,
            ],
        ];

        $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.proctoring-events.store', $attempt), $payload)
            ->assertCreated()
            ->assertJson([
                'recorded' => true,
                'duplicate' => false,
            ]);

        $this->actingAs($candidate)
            ->postJson(route('candidate.attempts.proctoring-events.store', $attempt), $payload)
            ->assertOk()
            ->assertJson([
                'recorded' => false,
                'duplicate' => true,
            ]);

        $this->assertDatabaseCount('proctoring_events', 1);
    }

    public function test_admin_result_review_includes_proctoring_summary_and_timeline(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $test = $this->publishedTestFor($admin);
        [, $attempt] = $this->publicAttemptFor($test, $admin, [
            'status' => AttemptStatus::Submitted,
            'submitted_at' => now(),
        ]);

        ProctoringEvent::create([
            'test_attempt_id' => $attempt->id,
            'candidate_user_id' => null,
            'event_type' => 'tab_hidden',
            'severity' => 'medium',
            'occurred_at' => now()->subMinutes(4),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'FeatureBrowser/1.0',
            'metadata' => [
                'visibility_state' => 'hidden',
            ],
        ]);
        ProctoringEvent::create([
            'test_attempt_id' => $attempt->id,
            'candidate_user_id' => null,
            'event_type' => 'copy_attempt',
            'severity' => 'high',
            'occurred_at' => now()->subMinutes(3),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'FeatureBrowser/1.0',
            'metadata' => [
                'fullscreen' => false,
            ],
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.tests.results.show', [$test, $attempt]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Results/Show')
            ->where('proctoring_summary.total', 2)
            ->where('proctoring_summary.high', 1)
            ->where('proctoring_summary.medium', 1)
            ->where('proctoring_summary.tab_switches', 1)
            ->where('proctoring_summary.clipboard_attempts', 1)
            ->where('proctoring_events.total', 2)
            ->where('proctoring_events.per_page', 15)
            ->where('proctoring_events.data.0.event_type', 'tab_hidden')
            ->where('proctoring_events.data.0.severity', 'medium')
            ->where('proctoring_events.data.0.metadata.visibility_state', 'hidden')
            ->where('proctoring_events.data.1.event_type', 'copy_attempt')
            ->where('proctoring_events.data.1.severity', 'high'));
    }

    public function test_admin_proctoring_timeline_is_paginated(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $test = $this->publishedTestFor($admin);
        [, $attempt] = $this->publicAttemptFor($test, $admin, [
            'status' => AttemptStatus::Submitted,
            'submitted_at' => now(),
        ]);

        for ($index = 0; $index < 17; $index++) {
            ProctoringEvent::create([
                'test_attempt_id' => $attempt->id,
                'candidate_user_id' => null,
                'event_type' => 'tab_hidden',
                'severity' => 'medium',
                'occurred_at' => now()->subMinutes(30 - $index),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'FeatureBrowser/1.0',
                'metadata' => [
                    'event_index' => $index,
                ],
            ]);
        }

        $firstPage = $this->actingAs($admin)
            ->get(route('admin.tests.results.show', [$test, $attempt]));

        $firstPage->assertOk();
        $firstPage->assertInertia(fn (Assert $page) => $page
            ->where('proctoring_summary.total', 17)
            ->has('proctoring_events.data', 15)
            ->where('proctoring_events.current_page', 1)
            ->where('proctoring_events.total', 17));

        $secondPage = $this->actingAs($admin)
            ->get(route('admin.tests.results.show', [$test, $attempt]).'?proctoring_page=2');

        $secondPage->assertOk();
        $secondPage->assertInertia(fn (Assert $page) => $page
            ->where('proctoring_summary.total', 17)
            ->where('proctoring_events.current_page', 2)
            ->has('proctoring_events.data', 2)
            ->where('proctoring_events.data.0.metadata.event_index', 15));
    }

    public function test_admin_result_summary_includes_blocking_control_counts(): void
    {
        $admin = $this->userWithRole(UserRole::Admin);
        $test = $this->publishedTestFor($admin);
        [, $attempt] = $this->publicAttemptFor($test, $admin, [
            'status' => AttemptStatus::Submitted,
            'submitted_at' => now(),
        ]);

        foreach ([
            'drag_attempt' => 'medium',
            'drop_attempt' => 'medium',
            'proctoring_violation_acknowledged' => 'low',
        ] as $eventType => $severity) {
            ProctoringEvent::create([
                'test_attempt_id' => $attempt->id,
                'candidate_user_id' => null,
                'event_type' => $eventType,
                'severity' => $severity,
                'occurred_at' => now(),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'FeatureBrowser/1.0',
                'metadata' => [],
            ]);
        }

        $response = $this->actingAs($admin)
            ->get(route('admin.tests.results.show', [$test, $attempt]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Results/Show')
            ->where('proctoring_summary.total', 3)
            ->where('proctoring_summary.drag_drop_attempts', 2)
            ->where('proctoring_summary.acknowledged_violations', 1)
            ->where('proctoring_summary.medium', 2)
            ->where('proctoring_summary.low', 1));
    }

    public function test_candidate_result_page_does_not_expose_proctoring_events(): void
    {
        $candidate = $this->userWithRole(UserRole::Candidate);
        [, $attempt] = $this->attemptForCandidate($candidate, [
            'status' => AttemptStatus::Submitted,
            'submitted_at' => now(),
        ]);

        ProctoringEvent::create([
            'test_attempt_id' => $attempt->id,
            'candidate_user_id' => $candidate->id,
            'event_type' => 'copy_attempt',
            'severity' => 'high',
            'occurred_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'proctor-only-user-agent',
            'metadata' => [
                'visibility_state' => 'hidden',
            ],
        ]);

        $response = $this->actingAs($candidate)
            ->get(route('candidate.attempts.show', $attempt));

        $response->assertOk()
            ->assertDontSee('proctor-only-user-agent');
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Candidate/Attempts/Result')
            ->missing('proctoring_summary')
            ->missing('proctoring_events'));
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
