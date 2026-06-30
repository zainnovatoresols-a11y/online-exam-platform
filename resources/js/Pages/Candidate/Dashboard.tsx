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

const actionLinkClassName =
    'inline-flex h-10 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-950 px-4 text-sm font-semibold text-zinc-100 transition hover:border-emerald-400 hover:text-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/40';

export default function Dashboard({
    invitations,
}: {
    invitations: Invitation[];
}) {
    return (
        <AuthenticatedLayout
            theme="dark"
            header={
                <h2 className="text-xl font-semibold leading-tight text-zinc-100">
                    Candidate Dashboard
                </h2>
            }
        >
            <Head title="Candidate Dashboard" />

            <div className="bg-zinc-950 py-10">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/20">
                        <p className="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-300">
                            Candidate workspace
                        </p>
                        <h1 className="mt-3 text-2xl font-semibold text-white">
                            My Tests
                        </h1>
                        <p className="mt-3 text-sm text-zinc-400">
                            Open your accepted tests from here.
                        </p>
                    </div>

                    <div className="overflow-hidden rounded-[18px] border border-zinc-800 bg-zinc-900 shadow-2xl shadow-black/20">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-zinc-800">
                                <thead className="bg-zinc-950/80">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500">
                                            Test
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500">
                                            Start time
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500">
                                            Questions
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-zinc-500">
                                            Action
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-zinc-800 bg-zinc-900">
                                    {invitations.map((invitation) => {
                                        const test = invitation.test;

                                        if (!test) {
                                            return null;
                                        }

                                        const status = invitation.attempt
                                            ? invitation.attempt.status
                                            : test.status;

                                        return (
                                            <tr
                                                key={invitation.id}
                                                className="transition hover:bg-zinc-800/40"
                                            >
                                                <td className="px-6 py-4 text-sm">
                                                    <div className="font-medium text-white">
                                                        {test.title}
                                                    </div>
                                                    <div className="mt-1 text-zinc-500">
                                                        {test.organization
                                                            ?.name ??
                                                            test.creator
                                                                ?.name ??
                                                            'Solo test'}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-sm">
                                                    <span
                                                        className={
                                                            'inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ' +
                                                            statusBadgeClassName(
                                                                status,
                                                            )
                                                        }
                                                    >
                                                        {formatLabel(status)}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-zinc-400">
                                                    {invitation.starts_at
                                                        ? formatDateTime(
                                                              invitation.starts_at,
                                                          )
                                                        : 'Available now'}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-zinc-400">
                                                    {test.questions_count}
                                                </td>
                                                <td className="px-6 py-4 text-right text-sm">
                                                    {invitation.attempt ? (
                                                        <Link
                                                            href={route(
                                                                'candidate.attempts.show',
                                                                invitation
                                                                    .attempt
                                                                    .id,
                                                            )}
                                                            className={
                                                                actionLinkClassName
                                                            }
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
                                                            className={
                                                                actionLinkClassName
                                                            }
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
                                                className="px-6 py-8 text-center text-sm text-zinc-500"
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

function statusBadgeClassName(status: string): string {
    const normalizedStatus = status.toLowerCase();

    if (
        normalizedStatus.includes('submitted') ||
        normalizedStatus.includes('published') ||
        normalizedStatus.includes('completed')
    ) {
        return 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200';
    }

    if (
        normalizedStatus.includes('closed') ||
        normalizedStatus.includes('expired') ||
        normalizedStatus.includes('failed')
    ) {
        return 'border-red-400/20 bg-red-400/10 text-red-200';
    }

    return 'border-amber-400/20 bg-amber-400/10 text-amber-200';
}

function formatLabel(value: string): string {
    return value
        .replace(/_/g, ' ')
        .split(' ')
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}
