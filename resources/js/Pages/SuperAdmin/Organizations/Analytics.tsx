import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, ReactNode } from 'react';

type Organization = {
    id: number;
    name: string;
};

type Filters = {
    from: string | null;
    to: string | null;
    test_status: string | null;
    review_status: string | null;
};

type Overview = {
    total_admins: number;
    total_tests: number;
    total_invitations: number;
    accepted_invitations: number;
    unique_candidates: number;
    started_attempts: number;
    submitted_attempts: number;
    in_progress_attempts: number;
    pass_count: number;
    fail_count: number;
    pass_rate: number | null;
    high_risk_attempts: number;
};

type StatusBreakdown = {
    draft?: number;
    published?: number;
    closed?: number;
    not_started?: number;
    in_progress?: number;
    submitted?: number;
    expired?: number;
};

type ScoreSummary = {
    average_score: number | null;
    highest_score: number | null;
    lowest_score: number | null;
    average_percentage: number | null;
};

type RiskBreakdown = {
    low_count: number;
    medium_count: number;
    high_count: number;
    critical_count: number;
    average_risk_score: number;
    highest_risk_score: number;
};

type ReviewBreakdown = {
    needs_review: number;
    approved: number;
    flagged: number;
    rejected: number;
};

type AdminActivity = {
    admin_id: number;
    name: string;
    email: string;
    tests_count: number;
    invitations_count: number;
    attempts_count: number;
};

type TestSummary = {
    test_id: number;
    title: string;
    status: string;
    creator: {
        id: number;
        name: string;
        email: string;
    } | null;
    invitations_count: number;
    attempts_count: number;
    submitted_attempts_count: number;
    pass_rate: number | null;
    average_score: number | null;
    average_risk_score: number;
    highest_risk_score: number;
    results_url: string;
    analytics_url: string;
};

type SuspiciousAttempt = {
    attempt_id: number;
    test_title: string | null;
    candidate_name: string | null;
    candidate_email: string | null;
    submitted_at: string | null;
    score: number | null;
    percentage: number | null;
    review_status: string;
    result_url: string;
    risk: {
        score: number;
        level: string;
    };
};

type Props = {
    organization: Organization;
    filters: Filters;
    overview: Overview;
    test_status_breakdown: StatusBreakdown;
    attempt_status_breakdown: StatusBreakdown;
    score_summary: ScoreSummary;
    risk_breakdown: RiskBreakdown;
    review_breakdown: ReviewBreakdown;
    admin_activity: AdminActivity[];
    test_summaries: TestSummary[];
    top_suspicious_attempts: SuspiciousAttempt[];
};

const cardClass =
    'rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/20';
const tableWrapperClass =
    'dark-horizontal-scrollbar overflow-x-auto rounded-[18px] border border-zinc-800 bg-zinc-900 shadow-2xl shadow-black/20';
const tableHeadingClass =
    'whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500';
const tableCellClass = 'whitespace-nowrap px-5 py-4 text-sm text-zinc-400';
const primaryButtonClass =
    'inline-flex h-11 items-center justify-center rounded-xl bg-emerald-500 px-5 text-sm font-bold text-black transition hover:bg-emerald-400';
const secondaryButtonClass =
    'inline-flex h-11 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-950 px-5 text-sm font-bold text-zinc-300 transition hover:border-emerald-500 hover:text-emerald-300';

const testStatusOptions = [
    { value: '', label: 'All test statuses' },
    { value: 'draft', label: 'Draft' },
    { value: 'published', label: 'Published' },
    { value: 'closed', label: 'Closed' },
];

const reviewStatusOptions = [
    { value: '', label: 'All review statuses' },
    { value: 'needs_review', label: 'Needs review' },
    { value: 'approved', label: 'Approved' },
    { value: 'flagged', label: 'Flagged' },
    { value: 'rejected', label: 'Rejected' },
];

export default function Analytics({
    organization,
    filters,
    overview,
    test_status_breakdown,
    attempt_status_breakdown,
    score_summary,
    risk_breakdown,
    review_breakdown,
    admin_activity,
    test_summaries,
    top_suspicious_attempts,
}: Props) {
    const form = useForm({
        from: filters.from ?? '',
        to: filters.to ?? '',
        test_status: filters.test_status ?? '',
        review_status: filters.review_status ?? '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.get(route('super-admin.organizations.analytics', organization.id), {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <AuthenticatedLayout
            theme="dark"
            header={
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                        Organization Owner
                    </p>
                    <h2 className="mt-2 text-xl font-semibold leading-tight text-white">
                        Organization Analytics
                    </h2>
                </div>
            }
        >
            <Head title={`${organization.name} Analytics`} />

            <div className="bg-zinc-950 px-4 py-10 text-zinc-100 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <section className={cardClass}>
                        <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                                    {organization.name}
                                </p>
                                <h1 className="mt-2 text-3xl font-bold text-white">
                                    Organization performance overview
                                </h1>
                                <p className="mt-3 max-w-3xl text-sm leading-relaxed text-zinc-400">
                                    Review tests, candidates, results, proctoring
                                    risk, and manual review status across this
                                    organization.
                                </p>
                            </div>
                            <Link
                                href={route(
                                    'super-admin.organizations.show',
                                    organization.id,
                                )}
                                className={secondaryButtonClass}
                            >
                                Back to organization
                            </Link>
                        </div>
                    </section>

                    <section className={cardClass}>
                        <form
                            onSubmit={submit}
                            className="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_1fr_auto_auto]"
                        >
                            <FilterField label="From">
                                <input
                                    type="date"
                                    value={form.data.from}
                                    onChange={(event) =>
                                        form.setData('from', event.target.value)
                                    }
                                    className={inputClass}
                                />
                            </FilterField>
                            <FilterField label="To">
                                <input
                                    type="date"
                                    value={form.data.to}
                                    onChange={(event) =>
                                        form.setData('to', event.target.value)
                                    }
                                    className={inputClass}
                                />
                            </FilterField>
                            <FilterField label="Test status">
                                <select
                                    value={form.data.test_status}
                                    onChange={(event) =>
                                        form.setData(
                                            'test_status',
                                            event.target.value,
                                        )
                                    }
                                    className={inputClass}
                                >
                                    {testStatusOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </FilterField>
                            <FilterField label="Review status">
                                <select
                                    value={form.data.review_status}
                                    onChange={(event) =>
                                        form.setData(
                                            'review_status',
                                            event.target.value,
                                        )
                                    }
                                    className={inputClass}
                                >
                                    {reviewStatusOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </FilterField>
                            <div className="flex items-end">
                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className={primaryButtonClass}
                                >
                                    Apply
                                </button>
                            </div>
                            <div className="flex items-end">
                                <Link
                                    href={route(
                                        'super-admin.organizations.analytics',
                                        organization.id,
                                    )}
                                    className={secondaryButtonClass}
                                >
                                    Reset
                                </Link>
                            </div>
                        </form>
                    </section>

                    <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <MetricCard label="Admins" value={overview.total_admins} />
                        <MetricCard label="Tests" value={overview.total_tests} />
                        <MetricCard
                            label="Candidates"
                            value={overview.unique_candidates}
                        />
                        <MetricCard
                            label="Submitted attempts"
                            value={overview.submitted_attempts}
                        />
                        <MetricCard
                            label="Pass rate"
                            value={formatPercent(overview.pass_rate)}
                        />
                        <MetricCard
                            label="Average score"
                            value={formatMetric(score_summary.average_score)}
                        />
                        <MetricCard
                            label="Average percentage"
                            value={formatPercent(score_summary.average_percentage)}
                        />
                        <MetricCard
                            label="High risk attempts"
                            value={overview.high_risk_attempts}
                        />
                    </section>

                    <section className="grid gap-6 lg:grid-cols-3">
                        <BreakdownCard
                            title="Test status"
                            rows={[
                                ['Draft', test_status_breakdown.draft ?? 0],
                                ['Published', test_status_breakdown.published ?? 0],
                                ['Closed', test_status_breakdown.closed ?? 0],
                            ]}
                        />
                        <BreakdownCard
                            title="Attempt status"
                            rows={[
                                ['Not started', attempt_status_breakdown.not_started ?? 0],
                                ['In progress', attempt_status_breakdown.in_progress ?? 0],
                                ['Submitted', attempt_status_breakdown.submitted ?? 0],
                                ['Expired', attempt_status_breakdown.expired ?? 0],
                            ]}
                        />
                        <BreakdownCard
                            title="Review decisions"
                            rows={[
                                ['Needs review', review_breakdown.needs_review],
                                ['Approved', review_breakdown.approved],
                                ['Flagged', review_breakdown.flagged],
                                ['Rejected', review_breakdown.rejected],
                            ]}
                        />
                    </section>

                    <section className="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                        <div className={cardClass}>
                            <h3 className="text-lg font-semibold text-white">
                                Proctoring risk
                            </h3>
                            <div className="mt-5 grid gap-3 sm:grid-cols-2">
                                <MetricCard
                                    label="Low"
                                    value={risk_breakdown.low_count}
                                />
                                <MetricCard
                                    label="Medium"
                                    value={risk_breakdown.medium_count}
                                />
                                <MetricCard
                                    label="High"
                                    value={risk_breakdown.high_count}
                                />
                                <MetricCard
                                    label="Critical"
                                    value={risk_breakdown.critical_count}
                                />
                                <MetricCard
                                    label="Average risk"
                                    value={risk_breakdown.average_risk_score}
                                />
                                <MetricCard
                                    label="Highest risk"
                                    value={risk_breakdown.highest_risk_score}
                                />
                            </div>
                        </div>

                        <div className={cardClass}>
                            <h3 className="text-lg font-semibold text-white">
                                Score summary
                            </h3>
                            <div className="mt-5 grid gap-3 sm:grid-cols-2">
                                <MetricCard
                                    label="Highest score"
                                    value={formatMetric(score_summary.highest_score)}
                                />
                                <MetricCard
                                    label="Lowest score"
                                    value={formatMetric(score_summary.lowest_score)}
                                />
                                <MetricCard
                                    label="Pass count"
                                    value={overview.pass_count}
                                />
                                <MetricCard
                                    label="Fail count"
                                    value={overview.fail_count}
                                />
                                <MetricCard
                                    label="Invitations"
                                    value={overview.total_invitations}
                                />
                                <MetricCard
                                    label="Accepted"
                                    value={overview.accepted_invitations}
                                />
                            </div>
                        </div>
                    </section>

                    <DataTable title="Admin activity">
                        <thead className="bg-zinc-950/80">
                            <tr>
                                <th className={tableHeadingClass}>Admin</th>
                                <th className={tableHeadingClass}>Tests</th>
                                <th className={tableHeadingClass}>Invites</th>
                                <th className={tableHeadingClass}>Attempts</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-800 bg-zinc-900">
                            {admin_activity.map((admin) => (
                                <tr key={admin.admin_id}>
                                    <td className="whitespace-nowrap px-5 py-4 text-sm">
                                        <div className="font-semibold text-white">
                                            {admin.name}
                                        </div>
                                        <div className="mt-1 text-xs text-zinc-500">
                                            {admin.email}
                                        </div>
                                    </td>
                                    <td className={tableCellClass}>
                                        {admin.tests_count}
                                    </td>
                                    <td className={tableCellClass}>
                                        {admin.invitations_count}
                                    </td>
                                    <td className={tableCellClass}>
                                        {admin.attempts_count}
                                    </td>
                                </tr>
                            ))}
                            {admin_activity.length === 0 && (
                                <EmptyRow colSpan={4} message="No admins found." />
                            )}
                        </tbody>
                    </DataTable>

                    <DataTable title="Test performance">
                        <thead className="bg-zinc-950/80">
                            <tr>
                                <th className={tableHeadingClass}>Test</th>
                                <th className={tableHeadingClass}>Status</th>
                                <th className={tableHeadingClass}>Attempts</th>
                                <th className={tableHeadingClass}>Pass rate</th>
                                <th className={tableHeadingClass}>Avg score</th>
                                <th className={tableHeadingClass}>Risk</th>
                                <th className="px-5 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-800 bg-zinc-900">
                            {test_summaries.map((test) => (
                                <tr key={test.test_id}>
                                    <td className="whitespace-nowrap px-5 py-4 text-sm">
                                        <div className="font-semibold text-white">
                                            {test.title}
                                        </div>
                                        <div className="mt-1 text-xs text-zinc-500">
                                            {test.creator?.name ?? 'Unknown admin'}
                                        </div>
                                    </td>
                                    <td className={tableCellClass}>
                                        <StatusBadge value={test.status} />
                                    </td>
                                    <td className={tableCellClass}>
                                        {test.submitted_attempts_count}/
                                        {test.attempts_count}
                                    </td>
                                    <td className={tableCellClass}>
                                        {formatPercent(test.pass_rate)}
                                    </td>
                                    <td className={tableCellClass}>
                                        {formatMetric(test.average_score)}
                                    </td>
                                    <td className={tableCellClass}>
                                        {test.highest_risk_score} max
                                    </td>
                                    <td className="px-5 py-4 text-right">
                                        <div className="flex justify-end gap-3">
                                            <Link
                                                href={test.results_url}
                                                className={secondaryButtonClass}
                                            >
                                                Results
                                            </Link>
                                            <Link
                                                href={test.analytics_url}
                                                className={secondaryButtonClass}
                                            >
                                                Analytics
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {test_summaries.length === 0 && (
                                <EmptyRow colSpan={7} message="No tests match these filters." />
                            )}
                        </tbody>
                    </DataTable>

                    <DataTable title="Top suspicious attempts">
                        <thead className="bg-zinc-950/80">
                            <tr>
                                <th className={tableHeadingClass}>Candidate</th>
                                <th className={tableHeadingClass}>Test</th>
                                <th className={tableHeadingClass}>Risk</th>
                                <th className={tableHeadingClass}>Score</th>
                                <th className={tableHeadingClass}>Review</th>
                                <th className="px-5 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-800 bg-zinc-900">
                            {top_suspicious_attempts.map((attempt) => (
                                <tr key={attempt.attempt_id}>
                                    <td className="whitespace-nowrap px-5 py-4 text-sm">
                                        <div className="font-semibold text-white">
                                            {attempt.candidate_name ?? 'Unknown candidate'}
                                        </div>
                                        <div className="mt-1 text-xs text-zinc-500">
                                            {attempt.candidate_email ?? '-'}
                                        </div>
                                    </td>
                                    <td className={tableCellClass}>
                                        {attempt.test_title ?? '-'}
                                    </td>
                                    <td className={tableCellClass}>
                                        <RiskBadge
                                            level={attempt.risk.level}
                                            score={attempt.risk.score}
                                        />
                                    </td>
                                    <td className={tableCellClass}>
                                        {formatMetric(attempt.score)}
                                    </td>
                                    <td className={tableCellClass}>
                                        <StatusBadge value={attempt.review_status} />
                                    </td>
                                    <td className="px-5 py-4 text-right">
                                        <Link
                                            href={attempt.result_url}
                                            className={secondaryButtonClass}
                                        >
                                            View result
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                            {top_suspicious_attempts.length === 0 && (
                                <EmptyRow colSpan={6} message="No suspicious attempts found." />
                            )}
                        </tbody>
                    </DataTable>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

const inputClass =
    'h-11 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 text-sm text-zinc-100 outline-none transition focus:border-emerald-500';

function FilterField({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <label className="block">
            <span className="mb-2 block text-xs font-semibold uppercase tracking-wider text-zinc-500">
                {label}
            </span>
            {children}
        </label>
    );
}

function MetricCard({
    label,
    value,
}: {
    label: string;
    value: string | number | null;
}) {
    return (
        <div className="rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3">
            <p className="text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                {label}
            </p>
            <p className="mt-2 text-2xl font-bold text-white">{value ?? '-'}</p>
        </div>
    );
}

function BreakdownCard({
    title,
    rows,
}: {
    title: string;
    rows: [string, number][];
}) {
    return (
        <div className={cardClass}>
            <h3 className="text-lg font-semibold text-white">{title}</h3>
            <div className="mt-5 space-y-3">
                {rows.map(([label, value]) => (
                    <div
                        key={label}
                        className="flex items-center justify-between gap-4 rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3"
                    >
                        <span className="text-sm text-zinc-400">{label}</span>
                        <span className="text-sm font-bold text-white">
                            {value}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}

function DataTable({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <section className={tableWrapperClass}>
            <div className="border-b border-zinc-800 px-6 py-4">
                <h3 className="text-lg font-semibold text-white">{title}</h3>
            </div>
            <div className="min-w-[900px]">
                <table className="min-w-full divide-y divide-zinc-800">
                    {children}
                </table>
            </div>
        </section>
    );
}

function EmptyRow({ colSpan, message }: { colSpan: number; message: string }) {
    return (
        <tr>
            <td
                colSpan={colSpan}
                className="px-5 py-8 text-center text-sm text-zinc-500"
            >
                {message}
            </td>
        </tr>
    );
}

function StatusBadge({ value }: { value: string }) {
    return (
        <span className="inline-flex rounded-full border border-zinc-700 bg-zinc-950 px-3 py-1 text-xs font-semibold capitalize text-zinc-300">
            {value.replace('_', ' ')}
        </span>
    );
}

function RiskBadge({ level, score }: { level: string; score: number }) {
    const colorClass =
        level === 'critical'
            ? 'border-red-500/40 bg-red-500/10 text-red-300'
            : level === 'high'
              ? 'border-orange-500/40 bg-orange-500/10 text-orange-300'
              : level === 'medium'
                ? 'border-yellow-500/40 bg-yellow-500/10 text-yellow-200'
                : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300';

    return (
        <span
            className={`inline-flex rounded-full border px-3 py-1 text-xs font-semibold capitalize ${colorClass}`}
        >
            {level} · {score}
        </span>
    );
}

function formatMetric(value: number | null): string {
    if (value === null) {
        return '-';
    }

    return new Intl.NumberFormat(undefined, {
        maximumFractionDigits: 2,
    }).format(value);
}

function formatPercent(value: number | null): string {
    if (value === null) {
        return '-';
    }

    return `${formatMetric(value)}%`;
}
