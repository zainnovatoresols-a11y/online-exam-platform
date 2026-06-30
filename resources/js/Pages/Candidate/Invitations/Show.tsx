import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, router } from '@inertiajs/react';

type Invitation = {
    token: string;
    email: string;
    name: string | null;
    starts_at: string | null;
    expires_at: string | null;
    test: {
        id: number;
        title: string;
        duration_minutes: number;
        pass_mark: number;
        status: string;
        organization: { id: number; name: string } | null;
        creator: { id: number; name: string; email: string } | null;
    };
};

export default function Show({ invitation }: { invitation: Invitation }) {
    const accept = () => {
        router.post(route('candidate.invitations.accept', invitation.token));
    };

    return (
        <GuestLayout theme="dark">
            <Head title="Candidate Invitation" />

            <div className="space-y-6">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-300">
                        Assessment invitation
                    </p>
                    <h1 className="mt-3 text-2xl font-semibold text-white">
                        {invitation.test.title}
                    </h1>
                    <p className="mt-3 text-sm text-zinc-400">
                        Invited email: {invitation.email}
                    </p>
                </div>

                <dl className="grid gap-3 text-sm sm:grid-cols-2">
                    <InvitationDetail
                        label="Owner"
                        value={
                            invitation.test.organization?.name ??
                            invitation.test.creator?.name ??
                            'Exam Admin'
                        }
                    />
                    <InvitationDetail
                        label="Duration"
                        value={`${invitation.test.duration_minutes} minutes`}
                    />
                    <InvitationDetail
                        label="Starts"
                        value={
                            invitation.starts_at
                                ? formatDateTime(invitation.starts_at)
                                : 'Available now'
                        }
                    />
                    <InvitationDetail
                        label="Expires"
                        value={
                            invitation.expires_at
                                ? formatDateTime(invitation.expires_at)
                                : 'No expiry'
                        }
                    />
                </dl>

                <PrimaryButton
                    type="button"
                    onClick={accept}
                    className="w-full justify-center !rounded-xl !border-emerald-500 !bg-emerald-500 !px-5 !py-2.5 !text-black hover:!bg-emerald-400 focus:!ring-emerald-500 focus:!ring-offset-zinc-950 sm:w-auto"
                >
                    Accept invitation
                </PrimaryButton>
            </div>
        </GuestLayout>
    );
}

function InvitationDetail({
    label,
    value,
}: {
    label: string;
    value: string;
}) {
    return (
        <div className="rounded-xl border border-zinc-800 bg-zinc-950/70 p-4">
            <dt className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                {label}
            </dt>
            <dd className="mt-2 font-medium text-zinc-100">{value}</dd>
        </div>
    );
}

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
