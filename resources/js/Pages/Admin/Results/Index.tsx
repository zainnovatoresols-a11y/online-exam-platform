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

type ProctoringRisk = {
    score: number;
    level: string;
    event_count: number;
};

type ResultRow = {
    invitation: Invitation;
    candidate: Candidate;
    attempt: Attempt | null;
    attempt_status: string;
    proctoring_risk: ProctoringRisk;
    proctoring_review_status: string;
};

type PaginatedResults = {
    data: ResultRow[];
    current_page?: number;
    from: number | null;
    last_page?: number;
    links?: PaginationLink[];
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Props = {
    test: Test;
    results: PaginatedResults;
};

const actionLinkClass =
    'inline-flex h-9 min-w-28 items-center justify-center rounded-xl border border-zinc-700 px-3 text-xs font-bold uppercase tracking-wider text-zinc-300 transition hover:border-zinc-600 hover:text-white';
const metricPanelClass =
    'flex min-h-24 flex-col justify-between rounded-2xl border border-zinc-800 bg-zinc-950/70 p-5';
const metricLabelClass =
    'text-[11px] font-semibold uppercase tracking-wider text-zinc-500';
const metricValueClass = 'mt-3 text-2xl font-bold text-white';
const tableHeadingClass =
    'whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500';
const tableHeadingCenterClass =
    'whitespace-nowrap px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500';
const tableCellClass =
    'whitespace-nowrap px-5 py-4 align-middle text-sm text-zinc-400';
const tableCellCenterClass =
    'whitespace-nowrap px-5 py-4 text-center align-middle text-sm text-zinc-400';
const scrollAreaClass =
    'dark-horizontal-scrollbar overflow-x-auto bg-zinc-950';
const paginationButtonClass =
    'inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-zinc-800 bg-zinc-950 px-3 text-sm font-semibold text-zinc-300 transition hover:border-zinc-600 hover:text-white';
const paginationActiveClass =
    'border-emerald-500 bg-emerald-500 text-black hover:border-emerald-400 hover:bg-emerald-400 hover:text-black';
const paginationDisabledClass =
    'cursor-not-allowed border-zinc-900 bg-zinc-950/70 text-zinc-600';

export default function Index({ test, results }: Props) {
    return (
        <AuthenticatedLayout
            theme="dark"
            header={
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                        Results Workspace
                    </p>
                    <h2 className="mt-2 text-xl font-semibold leading-tight text-white">
                        Test Results
                    </h2>
                </div>
            }
        >
            <Head title={`${test.title} Results`} />

            <div className="bg-zinc-950 px-4 py-10 text-zinc-100 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div className="rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/20 sm:p-8">
                        <div className="flex flex-col gap-6 border-b border-zinc-800 pb-6 lg:flex-row lg:items-start lg:justify-between">
                            <div className="max-w-3xl">
                                <Link
                                    href={route('admin.tests.show', test.id)}
                                    className="text-sm font-semibold text-zinc-400 underline-offset-4 transition hover:text-white hover:underline"
                                >
                                    Back to test
                                </Link>
                                <div className="mt-5">
                                    <StatusBadge value={test.status} />
                                </div>
                                <h1 className="mt-3 text-2xl font-bold text-white">
                                    {test.title}
                                </h1>
                                <p className="mt-3 text-sm leading-relaxed text-zinc-500">
                                    Owner:{' '}
                                    {test.organization?.name ??
                                        test.creator?.name ??
                                        'Solo admin'}
                                </p>
                            </div>

                            <div className="flex w-full flex-wrap gap-3 sm:w-auto sm:justify-end">
                                <Link
                                    href={route(
                                        'admin.tests.results.analytics',
                                        test.id,
                                    )}
                                    className={actionLinkClass}
                                >
                                    Analytics
                                </Link>
                                <a
                                    href={route(
                                        'admin.tests.results.export.csv',
                                        test.id,
                                    )}
                                    download
                                    className="inline-flex h-9 min-w-28 items-center justify-center rounded-xl bg-emerald-500 px-3 text-xs font-bold uppercase tracking-wider text-black transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                >
                                    Export CSV
                                </a>
                            </div>
                        </div>

                        <dl className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div className={metricPanelClass}>
                                <dt className={metricLabelClass}>
                                    Invitations
                                </dt>
                                <dd className={metricValueClass}>
                                    {test.invitations_count ?? 0}
                                </dd>
                            </div>
                            <div className={metricPanelClass}>
                                <dt className={metricLabelClass}>Attempts</dt>
                                <dd className={metricValueClass}>
                                    {test.attempts_count ?? 0}
                                </dd>
                            </div>
                            <div className={metricPanelClass}>
                                <dt className={metricLabelClass}>Pass mark</dt>
                                <dd className={metricValueClass}>
                                    {test.pass_mark}%
                                </dd>
                            </div>
                            <div className={metricPanelClass}>
                                <dt className={metricLabelClass}>Duration</dt>
                                <dd className={metricValueClass}>
                                    {test.duration_minutes} min
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div className="overflow-hidden rounded-[18px] border border-zinc-800 bg-zinc-900 shadow-2xl shadow-black/20">
                        <div className="border-b border-zinc-800 px-6 py-5">
                            <h3 className="text-sm font-semibold text-white">
                                Candidate results
                            </h3>
                            <p className="mt-1 text-xs text-zinc-500">
                                {results.total} result
                                {results.total === 1 ? '' : 's'} recorded
                            </p>
                        </div>

                        <div className={scrollAreaClass}>
                            <div className="min-w-[1400px]">
                                <table className="w-full divide-y divide-zinc-800">
                                <thead className="bg-zinc-950/70">
                                    <tr>
                                        <th className={tableHeadingClass}>
                                            Candidate
                                        </th>
                                        <th className={tableHeadingCenterClass}>
                                            Invitation
                                        </th>
                                        <th className={tableHeadingCenterClass}>
                                            Attempt
                                        </th>
                                        <th className={tableHeadingCenterClass}>
                                            Risk
                                        </th>
                                        <th className={tableHeadingCenterClass}>
                                            Review
                                        </th>
                                        <th className={tableHeadingCenterClass}>
                                            Score
                                        </th>
                                        <th className={tableHeadingCenterClass}>
                                            Result
                                        </th>
                                        <th className={tableHeadingClass}>
                                            Started
                                        </th>
                                        <th className={tableHeadingClass}>
                                            Submitted
                                        </th>
                                        <th className="px-5 py-3" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-zinc-800 bg-zinc-900">
                                    {results.data.map((row) => (
                                        <tr
                                            key={row.invitation.id}
                                            className="transition hover:bg-zinc-800/50"
                                        >
                                            <td className="min-w-72 px-5 py-4 align-middle text-sm">
                                                <div className="font-semibold text-white">
                                                    {row.candidate.name ??
                                                        row.invitation.name ??
                                                        'Unnamed'}
                                                </div>
                                                <div className="mt-1 text-zinc-300">
                                                    {row.candidate.email ??
                                                        row.invitation.email ??
                                                        'No email'}
                                                </div>
                                                <div className="mt-2 space-y-0.5 text-xs text-zinc-500">
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
                                            <td className={tableCellCenterClass}>
                                                <StatusBadge
                                                    value={
                                                        row.invitation.status
                                                    }
                                                />
                                            </td>
                                            <td className={tableCellCenterClass}>
                                                <StatusBadge
                                                    value={row.attempt_status}
                                                />
                                            </td>
                                            <td className={tableCellCenterClass}>
                                                <RiskLevelBadge
                                                    level={
                                                        row.proctoring_risk
                                                            .level
                                                    }
                                                />
                                                <div className="mt-2 text-xs text-zinc-500">
                                                    {
                                                        row.proctoring_risk
                                                            .score
                                                    }{' '}
                                                    points
                                                </div>
                                            </td>
                                            <td className={tableCellCenterClass}>
                                                <StatusBadge
                                                    value={
                                                        row.proctoring_review_status
                                                    }
                                                />
                                            </td>
                                            <td className={`${tableCellCenterClass} font-semibold text-white`}>
                                                {scoreLabel(row.attempt)}
                                            </td>
                                            <td className="whitespace-nowrap px-5 py-4 text-center align-middle text-sm">
                                                <ResultBadge
                                                    passed={
                                                        row.attempt?.passed ??
                                                        null
                                                    }
                                                />
                                                <div className="mt-2 text-xs text-zinc-500">
                                                    {percentageLabel(
                                                        row.attempt,
                                                    )}
                                                </div>
                                            </td>
                                            <td className={tableCellClass}>
                                                {formatDateTime(
                                                    row.attempt?.started_at,
                                                )}
                                            </td>
                                            <td className={tableCellClass}>
                                                {formatDateTime(
                                                    row.attempt?.submitted_at,
                                                )}
                                            </td>
                                            <td className="whitespace-nowrap px-5 py-4 text-right align-middle text-sm">
                                                {row.attempt && (
                                                    <Link
                                                        href={route(
                                                            'admin.tests.results.show',
                                                            [
                                                                test.id,
                                                                row.attempt.id,
                                                            ],
                                                        )}
                                                        className="font-semibold text-emerald-300 underline-offset-4 transition hover:text-emerald-200 hover:underline"
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
                                                colSpan={10}
                                                className="px-6 py-14 text-center text-sm text-zinc-500"
                                            >
                                                No candidates found for this
                                                test yet.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                                </table>

                            </div>
                        </div>
                    </div>

                    <ResultsPagination results={results} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function ResultsPagination({ results }: { results: PaginatedResults }) {
    if (results.total === 0) {
        return null;
    }

    const links = results.links ?? [];

    return (
        <div className="flex flex-col gap-4 px-1 text-sm sm:flex-row sm:items-center sm:justify-between">
            <p className="text-zinc-500">
                Showing {results.from ?? 0} to {results.to ?? 0} of{' '}
                {results.total} results
            </p>

            {links.length > 0 ? (
                <div className="flex flex-wrap items-center gap-2">
                    {links.map((link, index) =>
                        link.active ? (
                            <span
                                key={`${link.label}-${index}`}
                                className={`${paginationButtonClass} ${paginationActiveClass}`}
                            >
                                {paginationLabel(link.label)}
                            </span>
                        ) : link.url ? (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url}
                                preserveScroll
                                preserveState
                                className={paginationButtonClass}
                            >
                                {paginationLabel(link.label)}
                            </Link>
                        ) : (
                            <span
                                key={`${link.label}-${index}`}
                                className={`${paginationButtonClass} ${paginationDisabledClass}`}
                            >
                                {paginationLabel(link.label)}
                            </span>
                        ),
                    )}
                </div>
            ) : (
                <div className="flex flex-wrap items-center gap-2">
                    {results.prev_page_url ? (
                        <Link
                            href={results.prev_page_url}
                            preserveScroll
                            preserveState
                            className={paginationButtonClass}
                        >
                            {'< Prev'}
                        </Link>
                    ) : (
                        <span
                            className={`${paginationButtonClass} ${paginationDisabledClass}`}
                        >
                            {'< Prev'}
                        </span>
                    )}

                    {results.next_page_url ? (
                        <Link
                            href={results.next_page_url}
                            preserveScroll
                            preserveState
                            className={paginationButtonClass}
                        >
                            {'Next >'}
                        </Link>
                    ) : (
                        <span
                            className={`${paginationButtonClass} ${paginationDisabledClass}`}
                        >
                            {'Next >'}
                        </span>
                    )}
                </div>
            )}
        </div>
    );
}

function StatusBadge({ value }: { value: string }) {
    return (
        <span
            className={`inline-flex min-w-24 justify-center whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-semibold ${statusClassFor(value)}`}
        >
            {formatLabel(value)}
        </span>
    );
}

function ResultBadge({ passed }: { passed: boolean | null }) {
    if (passed === null) {
        return (
            <span className="inline-flex min-w-20 justify-center whitespace-nowrap rounded-full border border-zinc-600 bg-zinc-950 px-2.5 py-1 text-xs font-semibold text-zinc-300">
                Pending
            </span>
        );
    }

    return (
        <span
            className={
                'inline-flex min-w-20 justify-center whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-semibold ' +
                (passed
                    ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200'
                    : 'border-red-400/20 bg-red-400/10 text-red-200')
            }
        >
            {passed ? 'Passed' : 'Failed'}
        </span>
    );
}

function RiskLevelBadge({ level }: { level: string }) {
    const classes: Record<string, string> = {
        low: 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200',
        medium: 'border-amber-400/20 bg-amber-400/10 text-amber-200',
        high: 'border-orange-400/20 bg-orange-400/10 text-orange-200',
        critical: 'border-red-400/20 bg-red-400/10 text-red-200',
    };

    return (
        <span
            className={
                'inline-flex min-w-20 justify-center whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-semibold ' +
                (classes[level] ??
                    'border-zinc-600 bg-zinc-950 text-zinc-300')
            }
        >
            {formatLabel(level)}
        </span>
    );
}

function statusClassFor(value: string): string {
    const normalized = value.toLowerCase();

    if (
        ['accepted', 'approved', 'completed', 'passed', 'published'].includes(
            normalized,
        )
    ) {
        return 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200';
    }

    if (
        [
            'pending',
            'pending_review',
            'invited',
            'sent',
            'draft',
            'not_started',
        ].includes(normalized)
    ) {
        return 'border-amber-400/20 bg-amber-400/10 text-amber-200';
    }

    if (
        ['started', 'in_progress', 'running', 'submitted'].includes(normalized)
    ) {
        return 'border-sky-400/20 bg-sky-400/10 text-sky-200';
    }

    if (
        ['expired', 'failed', 'rejected', 'cancelled', 'closed'].includes(
            normalized,
        )
    ) {
        return 'border-red-400/20 bg-red-400/10 text-red-200';
    }

    return 'border-zinc-600 bg-zinc-950 text-zinc-300';
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

function paginationLabel(label: string): string {
    return label
        .replace('&laquo;', '<')
        .replace('&raquo;', '>')
        .replace('Previous', 'Prev')
        .trim();
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
