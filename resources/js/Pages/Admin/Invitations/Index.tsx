import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

type Test = {
    id: number;
    title: string;
    status: string;
    organization: { id: number; name: string } | null;
    creator: { id: number; name: string; email: string } | null;
};

type Invitation = {
    id: number;
    name: string | null;
    email: string;
    status: string;
    starts_at: string | null;
    expires_at: string | null;
    accepted_at: string | null;
};

type Props = {
    test: Test;
    canCreateInvitation: boolean;
    public_url: string | null;
    invitations: {
        data: Invitation[];
    };
};

export default function Index({
    test,
    canCreateInvitation,
    public_url,
    invitations,
}: Props) {
    const resend = (invitationId: number) => {
        router.post(
            route('admin.tests.invitations.resend', [test.id, invitationId]),
        );
    };

    const revoke = (invitationId: number) => {
        router.delete(
            route('admin.tests.invitations.revoke', [test.id, invitationId]),
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Candidate Invitations
                </h2>
            }
        >
            <Head title="Candidate Invitations" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap items-start justify-between gap-4 bg-white p-6 shadow-sm sm:rounded-lg">
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900">
                                {test.title}
                            </h3>
                            <p className="mt-1 text-sm text-gray-600">
                                Status: {test.status}
                            </p>
                            <p className="mt-1 text-sm text-gray-600">
                                Owner:{' '}
                                {test.organization?.name ??
                                    test.creator?.name ??
                                    'Solo admin'}
                            </p>
                        </div>
                        <div className="max-w-xl rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                            <p className="font-medium text-gray-900">
                                Public test URL
                            </p>
                            <p className="mt-1 break-all">
                                {public_url ?? 'Not generated yet'}
                            </p>
                            <p className="mt-2 text-xs text-gray-500">
                                Send this URL to candidates. Invited emails are
                                allowed even when open public access is off.
                            </p>
                        </div>

                        {canCreateInvitation && (
                            <Link
                                href={route(
                                    'admin.tests.invitations.create',
                                    test.id,
                                )}
                            >
                                <PrimaryButton type="button">
                                    Invite candidate
                                </PrimaryButton>
                            </Link>
                        )}
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Candidate
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Starts
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Expires
                                    </th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {invitations.data.map((invitation) => (
                                    <tr key={invitation.id}>
                                        <td className="px-6 py-4 text-sm">
                                            <div className="font-medium text-gray-900">
                                                {invitation.name ?? 'Unnamed'}
                                            </div>
                                            <div className="text-gray-600">
                                                {invitation.email}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {invitation.status}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {invitation.starts_at
                                                ? formatDateTime(
                                                      invitation.starts_at,
                                                  )
                                                : 'Available now'}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {invitation.expires_at
                                                ? formatDateTime(
                                                      invitation.expires_at,
                                                  )
                                                : 'No expiry'}
                                        </td>
                                        <td className="space-x-3 px-6 py-4 text-right text-sm">
                                            {['pending', 'sent'].includes(
                                                invitation.status,
                                            ) && (
                                                <>
                                                    <SecondaryButton
                                                        onClick={() =>
                                                            resend(
                                                                invitation.id,
                                                            )
                                                        }
                                                    >
                                                        Resend
                                                    </SecondaryButton>
                                                    <DangerButton
                                                        onClick={() =>
                                                            revoke(
                                                                invitation.id,
                                                            )
                                                        }
                                                    >
                                                        Revoke
                                                    </DangerButton>
                                                </>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {invitations.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-6 py-4 text-sm text-gray-600"
                                        >
                                            No invitations sent yet.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
