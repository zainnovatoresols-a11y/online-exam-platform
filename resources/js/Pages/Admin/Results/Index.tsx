import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type Test = {
    id: number;
    title: string;
    status: string;
    duration_minutes: number;
    pass_mark: number;
    starts_at: string | null;
    organization: { id: number; name: string } | null;
    creator: { id: number; name: string; email: string } | null;
    invitations_count: number | null;
    attempts_count: number | null;
};

type Candidate = {
    id: number | null;
    name: string | null;
    email: string | null;
    phone: string | null;
    stack_name: string | null;
    fields: Record<string, unknown>;
    details_submitted_at: string | null;
};

type Invitation = {
    id: number;
    name: string | null;
    email: string | null;
    status: string;
    starts_at: string | null;
    expires_at: string | null;
    accepted_at: string | null;
    policy_accepted_at: string | null;
};

type Attempt = {
    id: number;
    status: string;
    score: number;
    max_score: number;
    total_marks: number;
    percentage: number | null;
    passed: boolean | null;
    started_at: string | null;
    submitted_at: string | null;
    expires_at: string | null;
};

type ResultRow = {
    invitation: Invitation;
    candidate: Candidate;
    attempt: Attempt | null;
    attempt_status: string;
    proctoring_review_status: string;
};

type PaginatedResults = {
    data: ResultRow[];
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Props = {
    test: Test;
    results: PaginatedResults;
};

export default function Index({ test, results }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Test Results
                </h2>
            }
        >
            <Head title={`${test.title} Results`} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <Link
                                    href={route('admin.tests.show', test.id)}
                                    className="text-sm font-medium text-gray-600 underline"
                                >
                                    Back to test
                                </Link>
                                <p className="mt-3 text-sm font-medium uppercase text-gray-500">
                                    {test.status}
                                </p>
                                <h3 className="mt-2 text-lg font-semibold text-gray-900">
                                    {test.title}
                                </h3>
                                <p className="mt-2 text-sm text-gray-600">
                                    Owner:{' '}
                                    {test.organization?.name ??
                                        test.creator?.name ??
                                        'Solo admin'}
                                </p>
                            </div>

                            <div className="space-y-4">
                                <div className="flex justify-end">
                                    <a
                                        href={route(
                                            'admin.tests.results.export.csv',
                                            test.id,
                                        )}
                                        download
                                        className="rounded-md bg-gray-900 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white"
                                    >
                                        Export CSV
                                    </a>
                                </div>

                                <dl className="grid min-w-64 grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <dt className="font-medium text-gray-500">
                                            Invitations
                                        </dt>
                                        <dd className="mt-1 text-gray-900">
                                            {test.invitations_count ?? 0}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="font-medium text-gray-500">
                                            Attempts
                                        </dt>
                                        <dd className="mt-1 text-gray-900">
                                            {test.attempts_count ?? 0}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="font-medium text-gray-500">
                                            Pass mark
                                        </dt>
                                        <dd className="mt-1 text-gray-900">
                                            {test.pass_mark}%
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="font-medium text-gray-500">
                                            Duration
                                        </dt>
                                        <dd className="mt-1 text-gray-900">
                                            {test.duration_minutes} min
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Candidate
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Invitation
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Attempt
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Review
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Score
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Result
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Started
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Submitted
                                        </th>
                                        <th className="px-6 py-3" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {results.data.map((row) => (
                                        <tr key={row.invitation.id}>
                                            <td className="px-6 py-4 text-sm">
                                                <div className="font-medium text-gray-900">
                                                    {row.candidate.name ??
                                                        row.invitation.name ??
                                                        'Unnamed'}
                                                </div>
                                                <div className="text-gray-600">
                                                    {row.candidate.email ??
                                                        row.invitation.email ??
                                                        'No email'}
                                                </div>
                                                <div className="mt-1 space-y-0.5 text-xs text-gray-500">
                                                    {row.candidate.phone && (
                                                        <div>
                                                            Phone:{' '}
                                                            {
                                                                row.candidate
                                                                    .phone
                                                            }
                                                        </div>
                                                    )}
                                                    {row.candidate
                                                        .stack_name && (
                                                        <div>
                                                            Stack:{' '}
                                                            {
                                                                row.candidate
                                                                    .stack_name
                                                            }
                                                        </div>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600">
                                                <StatusBadge
                                                    value={
                                                        row.invitation.status
                                                    }
                                                />
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600">
                                                <StatusBadge
                                                    value={row.attempt_status}
                                                />
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600">
                                                <StatusBadge
                                                    value={
                                                        row.proctoring_review_status
                                                    }
                                                />
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600">
                                                {scoreLabel(row.attempt)}
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                <ResultBadge
                                                    passed={
                                                        row.attempt?.passed ??
                                                        null
                                                    }
                                                />
                                                <div className="mt-1 text-xs text-gray-500">
                                                    {percentageLabel(
                                                        row.attempt,
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600">
                                                {formatDateTime(
                                                    row.attempt?.started_at,
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600">
                                                {formatDateTime(
                                                    row.attempt?.submitted_at,
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm">
                                                {row.attempt && (
                                                    <Link
                                                        href={route(
                                                            'admin.tests.results.show',
                                                            [
                                                                test.id,
                                                                row.attempt.id,
                                                            ],
                                                        )}
                                                        className="font-medium text-gray-900 underline"
                                                    >
                                                        View details
                                                    </Link>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                    {results.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={9}
                                                className="px-6 py-4 text-sm text-gray-600"
                                            >
                                                No candidates found for this
                                                test yet.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {(results.prev_page_url || results.next_page_url) && (
                            <div className="flex items-center justify-between border-t border-gray-200 px-6 py-4 text-sm">
                                <div className="text-gray-600">
                                    Showing {results.from ?? 0} to{' '}
                                    {results.to ?? 0} of {results.total}
                                </div>
                                <div className="flex gap-3">
                                    {results.prev_page_url && (
                                        <Link
                                            href={results.prev_page_url}
                                            className="rounded-md border border-gray-300 px-3 py-2 font-medium text-gray-700"
                                        >
                                            Previous
                                        </Link>
                                    )}
                                    {results.next_page_url && (
                                        <Link
                                            href={results.next_page_url}
                                            className="rounded-md border border-gray-300 px-3 py-2 font-medium text-gray-700"
                                        >
                                            Next
                                        </Link>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function StatusBadge({ value }: { value: string }) {
    return (
        <span className="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
            {formatLabel(value)}
        </span>
    );
}

function ResultBadge({ passed }: { passed: boolean | null }) {
    if (passed === null) {
        return (
            <span className="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                Pending
            </span>
        );
    }

    return (
        <span
            className={
                'inline-flex rounded-full px-2.5 py-1 text-xs font-medium ' +
                (passed
                    ? 'bg-green-100 text-green-700'
                    : 'bg-red-100 text-red-700')
            }
        >
            {passed ? 'Passed' : 'Failed'}
        </span>
    );
}

function scoreLabel(attempt: Attempt | null): string {
    if (!attempt) {
        return 'Not started';
    }

    return `${attempt.score}/${attempt.max_score}`;
}

function percentageLabel(attempt: Attempt | null): string {
    if (!attempt || attempt.percentage === null) {
        return 'No percentage yet';
    }

    return `${attempt.percentage.toFixed(2)}%`;
}

function formatDateTime(value?: string | null): string {
    if (!value) {
        return 'Not recorded';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function formatLabel(value: string): string {
    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}
