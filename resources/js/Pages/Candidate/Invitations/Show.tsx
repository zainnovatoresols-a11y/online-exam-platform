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
        <GuestLayout>
            <Head title="Candidate Invitation" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-xl font-semibold text-gray-900">
                        {invitation.test.title}
                    </h1>
                    <p className="mt-2 text-sm text-gray-600">
                        Invited email: {invitation.email}
                    </p>
                </div>

                <dl className="space-y-3 text-sm">
                    <div>
                        <dt className="font-medium text-gray-900">Owner</dt>
                        <dd className="text-gray-600">
                            {invitation.test.organization?.name ??
                                invitation.test.creator?.name ??
                                'Exam Admin'}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-medium text-gray-900">Duration</dt>
                        <dd className="text-gray-600">
                            {invitation.test.duration_minutes} minutes
                        </dd>
                    </div>
                    <div>
                        <dt className="font-medium text-gray-900">Starts</dt>
                        <dd className="text-gray-600">
                            {invitation.starts_at
                                ? formatDateTime(invitation.starts_at)
                                : 'Available now'}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-medium text-gray-900">Expires</dt>
                        <dd className="text-gray-600">
                            {invitation.expires_at ?? 'No expiry'}
                        </dd>
                    </div>
                </dl>

                <PrimaryButton type="button" onClick={accept}>
                    Accept invitation
                </PrimaryButton>
            </div>
        </GuestLayout>
    );
}

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
