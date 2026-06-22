import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';

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

type Filters = {
    from: string | null;
    to: string | null;
    status: string | null;
    review_status: string | null;
};

type Overview = {
    total_invitations: number;
    accepted_invitations: number;
    started_attempts: number;
    submitted_attempts: number;
    in_progress_attempts: number;
    pass_count: number;
    fail_count: number;
    pass_rate: number | null;
};

type ScoreSummary = {
    average_score: number | null;
    highest_score: number | null;
    lowest_score: number | null;
    average_percentage: number | null;
    pass_percentage: number | null;
    mcq_average_score: number | null;
    coding_average_score: number | null;
};

type StatusBreakdown = {
    not_started: number;
    in_progress: number;
    submitted: number;
    expired: number;
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

type TimingSummary = {
    average_completion_seconds: number | null;
    fastest_completion_seconds: number | null;
    slowest_completion_seconds: number | null;
    average_time_before_submission_seconds: number | null;
};

type QuestionAnalyticsRow = {
    question_id: number;
    order: number;
    type: string;
    body: string;
    marks: number;
    attempted_count: number;
    average_awarded_score: number;
    average_percentage: number;
    zero_score_count: number;
    full_score_count: number;
    success_rate: number;
};

type ProctoringRisk = {
    score: number;
    level: string;
    event_count: number;
};

type SuspiciousAttempt = {
    attempt_id: number;
    candidate_name: string | null;
    candidate_email: string | null;
    submitted_at: string | null;
    score: number;
    max_score: number;
    percentage: number | null;
    review_status: string;
    risk: ProctoringRisk;
    result_url: string;
};

type SubmissionTrendPoint = {
    date: string;
    submitted_count: number;
    average_score: number;
};

type Props = {
    test: Test;
    filters: Filters;
    overview: Overview;
    score_summary: ScoreSummary;
    status_breakdown: StatusBreakdown;
    risk_breakdown: RiskBreakdown;
    review_breakdown: ReviewBreakdown;
    timing_summary: TimingSummary;
    question_analytics: QuestionAnalyticsRow[];
    top_suspicious_attempts: SuspiciousAttempt[];
    submission_trend: SubmissionTrendPoint[];
};

const statusOptions = [
    { value: '', label: 'All attempts' },
    { value: 'submitted', label: 'Submitted' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'expired', label: 'Expired' },
];

const reviewStatusOptions = [
    { value: '', label: 'All reviews' },
    { value: 'needs_review', label: 'Needs Review' },
    { value: 'approved', label: 'Approved' },
    { value: 'flagged', label: 'Flagged' },
    { value: 'rejected', label: 'Rejected' },
];

export default function Analytics({
    test,
    filters,
    overview,
    score_summary,
    status_breakdown,
    risk_breakdown,
    review_breakdown,
    timing_summary,
    question_analytics,
    top_suspicious_attempts,
    submission_trend,
}: Props) {
    const form = useForm({
        from: filters.from ?? '',
        to: filters.to ?? '',
        status: filters.status ?? '',
        review_status: filters.review_status ?? '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.get(route('admin.tests.results.analytics', test.id), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const resetFilters = () => {
        form.setData({
            from: '',
            to: '',
            status: '',
            review_status: '',
        });

        router.get(route('admin.tests.results.analytics', test.id), undefined, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Result Analytics
                </h2>
            }
        >
            <Head title={`${test.title} Analytics`} />

            <div className="py-10">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <section className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <Link
                                    href={route(
                                        'admin.tests.results.index',
                                        test.id,
                                    )}
                                    className="text-sm font-medium text-gray-600 underline"
                                >
                                    Back to results
                                </Link>
                                <div className="mt-3 flex flex-wrap items-center gap-2">
                                    <StatusBadge value={test.status} />
                                </div>
                                <h3 className="mt-3 text-lg font-semibold text-gray-900">
                                    {test.title}
                                </h3>
                                <p className="mt-2 text-sm text-gray-600">
                                    Owner:{' '}
                                    {test.organization?.name ??
                                        test.creator?.name ??
                                        'Solo admin'}
                                </p>
                            </div>

                            <div className="flex flex-wrap gap-3">
                                <a
                                    href={route(
                                        'admin.tests.results.export.csv',
                                        test.id,
                                    )}
                                    download
                                    className="rounded-md border border-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700"
                                >
                                    Export CSV
                                </a>
                                <Link
                                    href={route(
                                        'admin.tests.results.index',
                                        test.id,
                                    )}
                                    className="rounded-md bg-gray-900 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white"
                                >
                                    View Results
                                </Link>
                            </div>
                        </div>
                    </section>

                    <section className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h4 className="text-base font-semibold text-gray-900">
                                    Filters
                                </h4>
                                <p className="mt-1 text-sm text-gray-600">
                                    Narrow analytics by attempt window and review
                                    state.
                                </p>
                            </div>
                        </div>

                        <form
                            onSubmit={submit}
                            className="mt-5 grid gap-4 lg:grid-cols-[repeat(4,minmax(0,1fr))_auto]"
                        >
                            <FilterField label="From">
                                <input
                                    type="date"
                                    value={form.data.from}
                                    onChange={(event) =>
                                        form.setData('from', event.target.value)
                                    }
                                    className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900"
                                />
                            </FilterField>

                            <FilterField label="To">
                                <input
                                    type="date"
                                    value={form.data.to}
                                    onChange={(event) =>
                                        form.setData('to', event.target.value)
                                    }
                                    className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900"
                                />
                            </FilterField>

                            <FilterField label="Attempt status">
                                <select
                                    value={form.data.status}
                                    onChange={(event) =>
                                        form.setData(
                                            'status',
                                            event.target.value,
                                        )
                                    }
                                    className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900"
                                >
                                    {statusOptions.map((option) => (
                                        <option
                                            key={option.value || 'all'}
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
                                    className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900"
                                >
                                    {reviewStatusOptions.map((option) => (
                                        <option
                                            key={option.value || 'all'}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </FilterField>

                            <div className="flex flex-wrap items-end gap-3">
                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="rounded-md bg-gray-900 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white disabled:opacity-60"
                                >
                                    Apply
                                </button>
                                <button
                                    type="button"
                                    onClick={resetFilters}
                                    className="rounded-md border border-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700"
                                >
                                    Reset
                                </button>
                            </div>
                        </form>
                    </section>

                    <AnalyticsSection
                        title="Overview"
                        description="Participation funnel and completion outcome."
                    >
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <MetricCard
                                label="Total invitations"
                                value={overview.total_invitations}
                            />
                            <MetricCard
                                label="Accepted invitations"
                                value={overview.accepted_invitations}
                            />
                            <MetricCard
                                label="Started attempts"
                                value={overview.started_attempts}
                            />
                            <MetricCard
                                label="Submitted attempts"
                                value={overview.submitted_attempts}
                            />
                            <MetricCard
                                label="In progress"
                                value={overview.in_progress_attempts}
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
                                label="Pass rate"
                                value={formatPercent(overview.pass_rate)}
                            />
                        </div>
                    </AnalyticsSection>

                    <AnalyticsSection
                        title="Scores"
                        description="Performance summary from submitted attempts."
                    >
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <MetricCard
                                label="Average score"
                                value={formatMetric(score_summary.average_score)}
                            />
                            <MetricCard
                                label="Highest score"
                                value={formatMetric(score_summary.highest_score)}
                            />
                            <MetricCard
                                label="Lowest score"
                                value={formatMetric(score_summary.lowest_score)}
                            />
                            <MetricCard
                                label="Average percentage"
                                value={formatPercent(
                                    score_summary.average_percentage,
                                )}
                            />
                            <MetricCard
                                label="Pass percentage"
                                value={formatPercent(
                                    score_summary.pass_percentage,
                                )}
                            />
                            <MetricCard
                                label="MCQ average score"
                                value={formatMetric(
                                    score_summary.mcq_average_score,
                                )}
                            />
                            <MetricCard
                                label="Coding average score"
                                value={formatMetric(
                                    score_summary.coding_average_score,
                                )}
                            />
                        </div>
                    </AnalyticsSection>

                    <div className="grid gap-6 xl:grid-cols-2">
                        <AnalyticsSection
                            title="Attempt Status"
                            description="Current state across invited candidates."
                        >
                            <div className="grid gap-4 sm:grid-cols-2">
                                <MetricCard
                                    label="Not started"
                                    value={status_breakdown.not_started}
                                />
                                <MetricCard
                                    label="In progress"
                                    value={status_breakdown.in_progress}
                                />
                                <MetricCard
                                    label="Submitted"
                                    value={status_breakdown.submitted}
                                />
                                <MetricCard
                                    label="Expired"
                                    value={status_breakdown.expired}
                                />
                            </div>
                        </AnalyticsSection>

                        <AnalyticsSection
                            title="Timing"
                            description="How quickly candidates complete the test."
                        >
                            <div className="grid gap-4 sm:grid-cols-2">
                                <MetricCard
                                    label="Average completion"
                                    value={formatDuration(
                                        timing_summary.average_completion_seconds,
                                    )}
                                />
                                <MetricCard
                                    label="Fastest submitted"
                                    value={formatDuration(
                                        timing_summary.fastest_completion_seconds,
                                    )}
                                />
                                <MetricCard
                                    label="Slowest submitted"
                                    value={formatDuration(
                                        timing_summary.slowest_completion_seconds,
                                    )}
                                />
                                <MetricCard
                                    label="Average to submit"
                                    value={formatDuration(
                                        timing_summary.average_time_before_submission_seconds,
                                    )}
                                />
                            </div>
                        </AnalyticsSection>
                    </div>

                    <div className="grid gap-6 xl:grid-cols-2">
                        <AnalyticsSection
                            title="Proctoring Risk"
                            description="Transparent distribution from recorded events."
                        >
                            <div className="grid gap-4 sm:grid-cols-2">
                                <MetricCard
                                    label="Low risk"
                                    value={risk_breakdown.low_count}
                                />
                                <MetricCard
                                    label="Medium risk"
                                    value={risk_breakdown.medium_count}
                                />
                                <MetricCard
                                    label="High risk"
                                    value={risk_breakdown.high_count}
                                />
                                <MetricCard
                                    label="Critical risk"
                                    value={risk_breakdown.critical_count}
                                />
                                <MetricCard
                                    label="Average risk score"
                                    value={risk_breakdown.average_risk_score}
                                />
                                <MetricCard
                                    label="Highest risk score"
                                    value={risk_breakdown.highest_risk_score}
                                />
                            </div>
                        </AnalyticsSection>

                        <AnalyticsSection
                            title="Manual Review"
                            description="Current review decisions made by admins."
                        >
                            <div className="grid gap-4 sm:grid-cols-2">
                                <MetricCard
                                    label="Needs review"
                                    value={review_breakdown.needs_review}
                                />
                                <MetricCard
                                    label="Approved"
                                    value={review_breakdown.approved}
                                />
                                <MetricCard
                                    label="Flagged"
                                    value={review_breakdown.flagged}
                                />
                                <MetricCard
                                    label="Rejected"
                                    value={review_breakdown.rejected}
                                />
                            </div>
                        </AnalyticsSection>
                    </div>

                    <AnalyticsSection
                        title="Question Analytics"
                        description="Per-question performance across submitted attempts."
                    >
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        {[
                                            'Question',
                                            'Type',
                                            'Marks',
                                            'Attempted',
                                            'Average Score',
                                            'Average %',
                                            'Zero Score',
                                            'Full Score',
                                            'Success Rate',
                                        ].map((heading) => (
                                            <th
                                                key={heading}
                                                className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500"
                                            >
                                                {heading}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {question_analytics.map((question) => (
                                        <tr key={question.question_id}>
                                            <td className="px-4 py-3 text-sm text-gray-900">
                                                <div className="font-medium">
                                                    Q{question.order}
                                                </div>
                                                <div className="mt-1 max-w-xl text-gray-600">
                                                    {question.body}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                <TypeBadge
                                                    value={question.type}
                                                />
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {question.marks}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {question.attempted_count}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {question.average_awarded_score.toFixed(
                                                    2,
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {question.average_percentage.toFixed(
                                                    2,
                                                )}
                                                %
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {question.zero_score_count}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {question.full_score_count}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {question.success_rate.toFixed(
                                                    2,
                                                )}
                                                %
                                            </td>
                                        </tr>
                                    ))}
                                    {question_analytics.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={9}
                                                className="px-4 py-6 text-sm text-gray-600"
                                            >
                                                No question analytics are
                                                available for this test yet.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </AnalyticsSection>

                    <AnalyticsSection
                        title="Top Suspicious Attempts"
                        description="Highest-risk attempts sorted for quick review."
                    >
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        {[
                                            'Candidate',
                                            'Submitted',
                                            'Score',
                                            'Risk',
                                            'Review',
                                            '',
                                        ].map((heading) => (
                                            <th
                                                key={heading}
                                                className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500"
                                            >
                                                {heading}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {top_suspicious_attempts.map((attempt) => (
                                        <tr key={attempt.attempt_id}>
                                            <td className="px-4 py-3 text-sm">
                                                <div className="font-medium text-gray-900">
                                                    {attempt.candidate_name ??
                                                        'Unnamed'}
                                                </div>
                                                <div className="text-gray-600">
                                                    {attempt.candidate_email ??
                                                        'No email'}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {formatDateTime(
                                                    attempt.submitted_at,
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {attempt.score}/
                                                {attempt.max_score}
                                                <div className="mt-1 text-xs text-gray-500">
                                                    {formatPercent(
                                                        attempt.percentage,
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                <RiskLevelBadge
                                                    level={
                                                        attempt.risk.level
                                                    }
                                                />
                                                <div className="mt-1 text-xs text-gray-500">
                                                    {attempt.risk.score} points
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                <ReviewStatusBadge
                                                    value={
                                                        attempt.review_status
                                                    }
                                                />
                                            </td>
                                            <td className="px-4 py-3 text-right text-sm">
                                                <Link
                                                    href={attempt.result_url}
                                                    className="font-medium text-gray-900 underline"
                                                >
                                                    View result
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                    {top_suspicious_attempts.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={6}
                                                className="px-4 py-6 text-sm text-gray-600"
                                            >
                                                No suspicious attempts are
                                                available for the current
                                                filter set.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </AnalyticsSection>

                    <AnalyticsSection
                        title="Submission Trend"
                        description="Daily submission count and average score."
                    >
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Date
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Submitted
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Average score
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {submission_trend.map((point) => (
                                        <tr key={point.date}>
                                            <td className="px-4 py-3 text-sm text-gray-900">
                                                {formatDateOnly(point.date)}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {point.submitted_count}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {point.average_score.toFixed(2)}
                                            </td>
                                        </tr>
                                    ))}
                                    {submission_trend.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={3}
                                                className="px-4 py-6 text-sm text-gray-600"
                                            >
                                                No submitted attempts are
                                                available for the current
                                                filter set.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </AnalyticsSection>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function AnalyticsSection({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <section className="bg-white p-6 shadow-sm sm:rounded-lg">
            <div>
                <h4 className="text-base font-semibold text-gray-900">
                    {title}
                </h4>
                <p className="mt-1 text-sm text-gray-600">{description}</p>
            </div>
            <div className="mt-5">{children}</div>
        </section>
    );
}

function FilterField({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <label className="block">
            <span className="text-sm font-medium text-gray-500">{label}</span>
            <div className="mt-1">{children}</div>
        </label>
    );
}

function MetricCard({
    label,
    value,
}: {
    label: string;
    value: ReactNode;
}) {
    return (
        <div className="rounded-md border border-gray-200 px-4 py-4">
            <p className="text-sm font-medium text-gray-500">{label}</p>
            <p className="mt-2 text-2xl font-semibold text-gray-900">
                {value}
            </p>
        </div>
    );
}

function StatusBadge({ value }: { value: string }) {
    return (
        <span className="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
            {formatLabel(value)}
        </span>
    );
}

function ReviewStatusBadge({ value }: { value: string }) {
    const className =
        value === 'approved'
            ? 'bg-green-100 text-green-700'
            : value === 'rejected'
              ? 'bg-red-100 text-red-700'
              : value === 'flagged'
                ? 'bg-amber-100 text-amber-700'
                : 'bg-blue-100 text-blue-700';

    return (
        <span
            className={
                'inline-flex rounded-full px-2.5 py-1 text-xs font-medium ' +
                className
            }
        >
            {formatLabel(value)}
        </span>
    );
}

function RiskLevelBadge({ level }: { level: string }) {
    const classes: Record<string, string> = {
        low: 'bg-green-100 text-green-700',
        medium: 'bg-yellow-100 text-yellow-800',
        high: 'bg-orange-100 text-orange-800',
        critical: 'bg-red-100 text-red-700',
    };

    return (
        <span
            className={
                'inline-flex rounded-full px-2.5 py-1 text-xs font-medium ' +
                (classes[level] ?? 'bg-gray-100 text-gray-700')
            }
        >
            {formatLabel(level)}
        </span>
    );
}

function TypeBadge({ value }: { value: string }) {
    return (
        <span
            className={
                'inline-flex rounded-full px-2.5 py-1 text-xs font-medium ' +
                (value === 'coding'
                    ? 'bg-indigo-100 text-indigo-700'
                    : 'bg-gray-100 text-gray-700')
            }
        >
            {value === 'coding' ? 'Coding' : 'MCQ'}
        </span>
    );
}

function formatMetric(value: number | null): string {
    if (value === null) {
        return '-';
    }

    return value.toFixed(2);
}

function formatPercent(value: number | null): string {
    if (value === null) {
        return '-';
    }

    return `${value.toFixed(2)}%`;
}

function formatDuration(value: number | null): string {
    if (value === null) {
        return '-';
    }

    if (value < 60) {
        return `${Math.round(value)}s`;
    }

    const minutes = Math.floor(value / 60);
    const seconds = Math.round(value % 60);

    if (minutes < 60) {
        return `${minutes}m ${seconds}s`;
    }

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    return `${hours}h ${remainingMinutes}m`;
}

function formatDateTime(value: string | null): string {
    if (!value) {
        return 'Not submitted';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function formatDateOnly(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
    }).format(new Date(value));
}

function formatLabel(value: string): string {
    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}
