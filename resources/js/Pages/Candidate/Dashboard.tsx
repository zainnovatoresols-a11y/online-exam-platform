import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type Invitation = {
    id: number;
    starts_at: string | null;
    accepted_at: string | null;
    test: {
        id: number;
        title: string;
        status: string;
        duration_minutes: number;
        questions_count: number;
        organization: { id: number; name: string } | null;
        creator: { id: number; name: string; email: string } | null;
    } | null;
    attempt: {
        id: number;
        status: string;
        submitted_at: string | null;
    } | null;
};

export default function Dashboard({
    invitations,
}: {
    invitations: Invitation[];
}) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Candidate Dashboard
                </h2>
            }
        >
            <Head title="Candidate Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h1 className="text-2xl font-semibold text-gray-900">
                            My Tests
                        </h1>
                        <p className="mt-2 text-sm text-gray-600">
                            Open your accepted tests from here.
                        </p>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Test
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Start time
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Questions
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {invitations.map((invitation) => {
                                    const test = invitation.test;

                                    if (!test) {
                                        return null;
                                    }

                                    return (
                                        <tr key={invitation.id}>
                                            <td className="px-6 py-4 text-sm">
                                                <div className="font-medium text-gray-900">
                                                    {test.title}
                                                </div>
                                                <div className="text-gray-600">
                                                    {test.organization?.name ??
                                                        test.creator?.name ??
                                                        'Solo test'}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600">
                                                {invitation.attempt
                                                    ? invitation.attempt.status
                                                    : test.status}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600">
                                                {invitation.starts_at
                                                    ? formatDateTime(
                                                          invitation.starts_at,
                                                      )
                                                    : 'Available now'}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600">
                                                {test.questions_count}
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm">
                                                {invitation.attempt ? (
                                                    <Link
                                                        href={route(
                                                            'candidate.attempts.show',
                                                            invitation.attempt
                                                                .id,
                                                        )}
                                                        className="font-medium text-gray-900 underline"
                                                    >
                                                        {invitation.attempt
                                                            .status ===
                                                        'submitted'
                                                            ? 'View submission'
                                                            : 'Resume test'}
                                                    </Link>
                                                ) : (
                                                    <Link
                                                        href={route(
                                                            'candidate.tests.show',
                                                            test.id,
                                                        )}
                                                        className="font-medium text-gray-900 underline"
                                                    >
                                                        View test
                                                    </Link>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                                {invitations.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-6 py-4 text-sm text-gray-600"
                                        >
                                            No accepted tests yet.
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
