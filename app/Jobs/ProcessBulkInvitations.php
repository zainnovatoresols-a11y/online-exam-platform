<?php

namespace App\Jobs;

use App\Actions\Invitations\CreateInvitation;
use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Models\Test;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessBulkInvitations implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $emails
     */
    public function __construct(
        public int $testId,
        public int $adminId,
        public array $emails,
        public string $startsAt,
        public ?string $expiresAt,
        public ?string $urlRoot = null,
    ) {}

    public function handle(CreateInvitation $createInvitation): void
    {
        $test = Test::query()->find($this->testId);
        $admin = User::query()->find($this->adminId);

        if (! $test || ! $admin) {
            return;
        }

        foreach ($this->emails as $email) {
            if ($this->activeInvitationExists($test, $email)) {
                continue;
            }

            $createInvitation->handle($test, $admin, [
                'name' => null,
                'email' => $email,
                'starts_at' => $this->startsAt,
                'expires_at' => $this->expiresAt,
                'url_root' => $this->urlRoot,
            ]);
        }
    }

    private function activeInvitationExists(Test $test, string $email): bool
    {
        return Invitation::query()
            ->where('test_id', $test->id)
            ->where('email', $email)
            ->whereIn('status', [
                InvitationStatus::Pending->value,
                InvitationStatus::Sent->value,
                InvitationStatus::Accepted->value,
            ])
            ->exists();
    }
}
