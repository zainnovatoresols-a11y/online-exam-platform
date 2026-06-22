import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';

type Test = {
    id: number;
    title: string;
    status: string;
    duration_minutes: number;
    pass_mark: number;
    organization: { id: number; name: string } | null;
    creator: { id: number; name: string; email: string } | null;
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

type ProctoringSummary = {
    total: number;
    high: number;
    medium: number;
    low: number;
    tab_switches: number;
    fullscreen_exits: number;
    clipboard_attempts: number;
    right_click_attempts: number;
    shortcut_attempts: number;
    drag_drop_attempts: number;
    acknowledged_violations: number;
    recording_permission_denials: number;
    recording_errors: number;
    screen_share_ended: number;
};

type ProctoringEvent = {
    id: number;
    event_type: string;
    severity: 'low' | 'medium' | 'high' | string;
    occurred_at: string | null;
    ip_address: string | null;
    user_agent: string | null;
    metadata: Record<string, unknown>;
    created_at: string | null;
};

type ProctoringRiskBreakdown = {
    event_type: string;
    label: string;
    count: number;
    points_each: number;
    points: number;
};

type ProctoringRisk = {
    score: number;
    level: string;
    event_count: number;
    breakdown: ProctoringRiskBreakdown[];
};

type ProctoringReviewDecision = {
    id: number | null;
    status: string;
    risk_level: string | null;
    reason_codes: string[];
    notes: string | null;
    reviewed_at: string | null;
    reviewed_by: {
        id: number;
        name: string;
        email: string;
    } | null;
};

type ProctoringRecordingSummary = {
    camera_status: string;
    camera_chunk_count: number;
    camera_total_size_bytes: number;
    camera_started_at: string | null;
    camera_stopped_at: string | null;
    camera_merged_status: string;
    camera_merged_url: string | null;
    camera_merged_at: string | null;
    camera_merged_size_bytes: number;
    camera_merge_error: string | null;
    screen_status: string;
    screen_chunk_count: number;
    screen_total_size_bytes: number;
    screen_started_at: string | null;
    screen_stopped_at: string | null;
    screen_merged_status: string;
    screen_merged_url: string | null;
    screen_merged_at: string | null;
    screen_merged_size_bytes: number;
    screen_merge_error: string | null;
};

type ProctoringRecordingChunk = {
    id: number;
    recording_type: 'camera' | 'screen' | string;
    sequence: number;
    mime_type: string | null;
    size_bytes: number | null;
    duration_ms: number | null;
    recorded_at: string | null;
    uploaded_at: string | null;
    ip_address: string | null;
    user_agent: string | null;
    metadata: Record<string, unknown>;
    url: string;
    event: {
        id: number;
        event_type: string;
        severity: 'low' | 'medium' | 'high' | string;
        occurred_at: string | null;
    } | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    next_page_url: string | null;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
};

type Question = {
    id: number;
    type: 'mcq' | 'coding' | string;
    body: string;
    marks: number;
    order: number;
};

type Option = {
    id: number;
    body: string;
    is_correct?: boolean;
};

type TestCaseSummary = {
    total: number;
    passed: number;
    failed: number;
};

type CodeExecutionTestCaseResult = {
    id: number;
    question_test_case_id: number | null;
    is_hidden: boolean;
    status: string;
    passed: boolean;
    input: string | null;
    expected_output: string | null;
    actual_output: string | null;
    stdout: string | null;
    stderr: string | null;
    compile_output: string | null;
    message: string | null;
    time: string | number | null;
    memory: number | null;
    judge0_status_id: number | null;
    judge0_status_description: string | null;
};

type CodeExecutionRun = {
    id: number;
    status: string;
    run_type: string;
    language: string;
    score_awarded: number | null;
    max_score: number | null;
    passed: boolean | null;
    result_summary: Record<string, unknown> | null;
    error_message: string | null;
    visible_summary: TestCaseSummary;
    hidden_summary: TestCaseSummary;
    started_at: string | null;
    finished_at: string | null;
    test_case_results: CodeExecutionTestCaseResult[];
};

type Answer = {
    id: number;
    type: 'mcq' | 'coding' | string;
    question: Question | null;
    selected_option: Option | null;
    correct_options: Option[];
    language: string | null;
    submitted_code: string | null;
    is_correct: boolean;
    score: number;
    execution_run: CodeExecutionRun | null;
};

type Props = {
    test: Test;
    invitation: Invitation | null;
    candidate: Candidate;
    attempt: Attempt;
    answers: Answer[];
    proctoring_summary: ProctoringSummary;
    proctoring_risk: ProctoringRisk;
    proctoring_events: Paginated<ProctoringEvent>;
    proctoring_review: ProctoringReviewDecision;
    proctoring_recording_summary: ProctoringRecordingSummary;
    proctoring_camera_recording_chunks: Paginated<ProctoringRecordingChunk>;
    proctoring_screen_recording_chunks: Paginated<ProctoringRecordingChunk>;
};

export default function Show({
    test,
    invitation,
    candidate,
    attempt,
    answers,
    proctoring_summary,
    proctoring_risk,
    proctoring_events,
    proctoring_review,
    proctoring_recording_summary,
    proctoring_camera_recording_chunks,
    proctoring_screen_recording_chunks,
}: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Attempt Result
                </h2>
            }
        >
            <Head title={`${candidate.name ?? 'Candidate'} Result`} />

            <div className="py-8">
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
                                    <StatusBadge value={attempt.status} />
                                </div>
                                <h3 className="mt-3 text-lg font-semibold text-gray-900">
                                    {test.title}
                                </h3>
                                <p className="mt-2 text-sm text-gray-600">
                                    {candidate.name ?? 'Unnamed candidate'} -{' '}
                                    {candidate.email ?? 'No email'}
                                </p>
                                <p className="mt-1 text-sm text-gray-500">
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
                                            'admin.tests.results.attempts.export.pdf',
                                            [test.id, attempt.id],
                                        )}
                                        download
                                        className="rounded-md bg-gray-900 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white"
                                    >
                                        Export PDF
                                    </a>
                                </div>

                                <dl className="grid min-w-72 grid-cols-2 gap-4 text-sm">
                                    <Metric label="Score">
                                        {attempt.score}/{attempt.max_score}
                                    </Metric>
                                    <Metric label="Percentage">
                                        {attempt.percentage === null
                                            ? 'Pending'
                                            : `${attempt.percentage.toFixed(
                                                  2,
                                              )}%`}
                                    </Metric>
                                    <Metric label="Result">
                                        <ResultBadge
                                            passed={attempt.passed}
                                        />
                                    </Metric>
                                    <Metric label="Pass mark">
                                        {test.pass_mark}%
                                    </Metric>
                                </dl>
                            </div>
                        </div>
                    </section>

                    <div className="grid gap-6 lg:grid-cols-3">
                        <section className="bg-white p-6 shadow-sm sm:rounded-lg lg:col-span-2">
                            <h4 className="text-base font-semibold text-gray-900">
                                Candidate Profile
                            </h4>
                            <dl className="mt-4 grid gap-4 sm:grid-cols-2">
                                <Detail label="Name">
                                    {candidate.name ?? 'Not provided'}
                                </Detail>
                                <Detail label="Email">
                                    {candidate.email ?? 'Not provided'}
                                </Detail>
                                <Detail label="Phone">
                                    {candidate.phone ?? 'Not provided'}
                                </Detail>
                                <Detail label="Stack">
                                    {candidate.stack_name ?? 'Not provided'}
                                </Detail>
                                <Detail label="Details submitted">
                                    {formatDateTime(
                                        candidate.details_submitted_at,
                                    )}
                                </Detail>
                                <Detail label="Invitation status">
                                    {invitation ? (
                                        <StatusBadge
                                            value={invitation.status}
                                        />
                                    ) : (
                                        'No invitation'
                                    )}
                                </Detail>
                            </dl>

                            {Object.keys(candidate.fields).length > 0 && (
                                <div className="mt-6 border-t border-gray-200 pt-4">
                                    <h5 className="text-sm font-semibold text-gray-900">
                                        Submitted Fields
                                    </h5>
                                    <dl className="mt-3 grid gap-3 sm:grid-cols-2">
                                        {Object.entries(candidate.fields).map(
                                            ([key, value]) => (
                                                <Detail
                                                    key={key}
                                                    label={formatLabel(key)}
                                                >
                                                    {formatFieldValue(value)}
                                                </Detail>
                                            ),
                                        )}
                                    </dl>
                                </div>
                            )}
                        </section>

                        <section className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <h4 className="text-base font-semibold text-gray-900">
                                Timeline
                            </h4>
                            <dl className="mt-4 space-y-4">
                                <Detail label="Started">
                                    {formatDateTime(attempt.started_at)}
                                </Detail>
                                <Detail label="Submitted">
                                    {formatDateTime(attempt.submitted_at)}
                                </Detail>
                                <Detail label="Expires">
                                    {formatDateTime(attempt.expires_at)}
                                </Detail>
                                <Detail label="Invitation accepted">
                                    {formatDateTime(invitation?.accepted_at)}
                                </Detail>
                                <Detail label="Policy accepted">
                                    {formatDateTime(
                                        invitation?.policy_accepted_at,
                                    )}
                                </Detail>
                            </dl>
                        </section>
                    </div>

                    <ProctoringReview
                        summary={proctoring_summary}
                        events={proctoring_events}
                    />

                    <ProctoringRiskScore
                        risk={proctoring_risk}
                        reviewStatus={proctoring_review.status}
                    />

                    <ProctoringRecordingReview
                        summary={proctoring_recording_summary}
                        cameraChunks={proctoring_camera_recording_chunks}
                        screenChunks={proctoring_screen_recording_chunks}
                    />

                    <section className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="border-b border-gray-200 px-6 py-4">
                            <h4 className="text-base font-semibold text-gray-900">
                                Answers And Execution Details
                            </h4>
                            <p className="mt-1 text-sm text-gray-600">
                                Coding results include hidden test cases for
                                admin review only.
                            </p>
                        </div>
                        <div className="divide-y divide-gray-200">
                            {answers.map((answer, index) => (
                                <article
                                    key={answer.id}
                                    className="space-y-5 px-6 py-5"
                                >
                                    <QuestionHeader
                                        answer={answer}
                                        index={index}
                                    />

                                    {answer.type === 'coding' ? (
                                        <CodingAnswer answer={answer} />
                                    ) : (
                                        <McqAnswer answer={answer} />
                                    )}
                                </article>
                            ))}

                            {answers.length === 0 && (
                                <div className="px-6 py-5 text-sm text-gray-600">
                                    No answers have been saved for this attempt
                                    yet.
                                </div>
                            )}
                        </div>
                    </section>

                    <ProctoringReviewDecisionForm
                        testId={test.id}
                        attemptId={attempt.id}
                        review={proctoring_review}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function ProctoringReview({
    summary,
    events,
}: {
    summary: ProctoringSummary;
    events: Paginated<ProctoringEvent>;
}) {
    return (
        <section className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="border-b border-gray-200 px-6 py-4">
                <h4 className="text-base font-semibold text-gray-900">
                    Proctoring Summary
                </h4>
                <p className="mt-1 text-sm text-gray-600">
                    Recorded browser activity for this attempt.
                </p>
            </div>

            <div className="space-y-6 p-6">
                <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Metric label="Total events">{summary.total}</Metric>
                    <Metric label="High severity">{summary.high}</Metric>
                    <Metric label="Tab switches">
                        {summary.tab_switches}
                    </Metric>
                    <Metric label="Fullscreen exits">
                        {summary.fullscreen_exits}
                    </Metric>
                    <Metric label="Clipboard attempts">
                        {summary.clipboard_attempts}
                    </Metric>
                    <Metric label="Right-click attempts">
                        {summary.right_click_attempts}
                    </Metric>
                    <Metric label="Shortcut attempts">
                        {summary.shortcut_attempts}
                    </Metric>
                    <Metric label="Drag/drop attempts">
                        {summary.drag_drop_attempts}
                    </Metric>
                    <Metric label="Acknowledgements">
                        {summary.acknowledged_violations}
                    </Metric>
                    <Metric label="Recording denials">
                        {summary.recording_permission_denials}
                    </Metric>
                    <Metric label="Recording errors">
                        {summary.recording_errors}
                    </Metric>
                    <Metric label="Screen ended">
                        {summary.screen_share_ended}
                    </Metric>
                    <Metric label="Low / Medium">
                        {summary.low} / {summary.medium}
                    </Metric>
                </dl>

                {events.data.length > 0 ? (
                    <div className="space-y-3">
                        <div className="max-h-[32rem] overflow-auto rounded-md border border-gray-200">
                        <table className="min-w-[960px] divide-y divide-gray-200 text-sm">
                            <thead className="sticky top-0 z-10 bg-gray-50">
                                <tr>
                                    {[
                                        'Time',
                                        'Event',
                                        'Severity',
                                        'IP',
                                        'User Agent',
                                        'Details',
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
                                {events.data.map((event) => (
                                    <tr key={event.id}>
                                        <td className="whitespace-nowrap px-4 py-3 align-top text-gray-700">
                                            {formatDateTime(
                                                event.occurred_at ??
                                                    event.created_at,
                                            )}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 align-top text-gray-700">
                                            {formatLabel(event.event_type)}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            <SeverityBadge
                                                value={event.severity}
                                            />
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 align-top text-gray-700">
                                            {formatNullableValue(
                                                event.ip_address,
                                            )}
                                        </td>
                                        <td className="max-w-sm whitespace-pre-wrap break-words px-4 py-3 align-top text-gray-700">
                                            {formatNullableValue(
                                                event.user_agent,
                                            )}
                                        </td>
                                        <td className="max-w-sm px-4 py-3 align-top text-gray-700">
                                            <MetadataDetails
                                                metadata={event.metadata}
                                            />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        </div>
                        <ProctoringPagination events={events} />
                    </div>
                ) : (
                    <div className="rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                        No proctoring events were recorded for this attempt.
                    </div>
                )}
            </div>
        </section>
    );
}

function ProctoringRiskScore({
    risk,
    reviewStatus,
}: {
    risk: ProctoringRisk;
    reviewStatus: string;
}) {
    const shouldReview =
        reviewStatus === 'needs_review' &&
        ['high', 'critical'].includes(risk.level);

    return (
        <section className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="border-b border-gray-200 px-6 py-4">
                <h4 className="text-base font-semibold text-gray-900">
                    Proctoring Risk Score
                </h4>
                <p className="mt-1 text-sm text-gray-600">
                    Advisory score calculated from recorded proctoring events.
                </p>
            </div>

            <div className="space-y-5 p-6">
                <dl className="grid gap-4 sm:grid-cols-3">
                    <Metric label="Risk level">
                        <RiskLevelBadge level={risk.level} />
                    </Metric>
                    <Metric label="Score">{risk.score} points</Metric>
                    <Metric label="Events">{risk.event_count}</Metric>
                </dl>

                {shouldReview && (
                    <div className="rounded-md border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-900">
                        This attempt has elevated proctoring risk and should be
                        reviewed manually.
                    </div>
                )}

                <div className="overflow-x-auto rounded-md border border-gray-200">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                {[
                                    'Event',
                                    'Count',
                                    'Points each',
                                    'Total points',
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
                            {risk.breakdown.map((item) => (
                                <tr
                                    key={`${item.event_type}-${item.points_each}`}
                                >
                                    <td className="px-4 py-3 text-gray-700">
                                        {item.label}
                                    </td>
                                    <td className="px-4 py-3 text-gray-700">
                                        {item.count}
                                    </td>
                                    <td className="px-4 py-3 text-gray-700">
                                        {item.points_each}
                                    </td>
                                    <td className="px-4 py-3 font-medium text-gray-900">
                                        {item.points}
                                    </td>
                                </tr>
                            ))}

                            {risk.breakdown.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-4 py-3 text-gray-600"
                                    >
                                        No risk events were recorded for this
                                        attempt.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    );
}

function ProctoringPagination({
    events,
}: {
    events: Paginated<ProctoringEvent>;
}) {
    if (events.total === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 text-sm">
            <p className="text-gray-600">
                Showing {events.from ?? 0} to {events.to ?? 0} of{' '}
                {events.total} events
            </p>

            {events.last_page > 1 && (
                <div className="flex flex-wrap items-center gap-1">
                    {events.links.map((link, index) =>
                        link.url ? (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url}
                                preserveScroll
                                preserveState
                                className={
                                    'rounded-md border px-3 py-1.5 text-sm font-medium ' +
                                    (link.active
                                        ? 'border-gray-900 bg-gray-900 text-white'
                                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50')
                                }
                            >
                                {paginationLabel(link.label)}
                            </Link>
                        ) : (
                            <span
                                key={`${link.label}-${index}`}
                                className="rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm font-medium text-gray-400"
                            >
                                {paginationLabel(link.label)}
                            </span>
                        ),
                    )}
                </div>
            )}
        </div>
    );
}

type ProctoringReviewFormData = {
    status: string;
    risk_level: string;
    reason_codes: string[];
    notes: string;
};

const reviewStatusOptions = [
    { value: 'approved', label: 'Approved' },
    { value: 'needs_review', label: 'Needs review' },
    { value: 'flagged', label: 'Flagged' },
    { value: 'rejected', label: 'Rejected' },
];

const riskLevelOptions = [
    { value: '', label: 'None' },
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
    { value: 'critical', label: 'Critical' },
];

const reasonCodeOptions = [
    { value: 'tab_switching', label: 'Tab switching' },
    { value: 'fullscreen_exit', label: 'Fullscreen exit' },
    { value: 'clipboard_attempt', label: 'Clipboard attempt' },
    { value: 'right_click_attempt', label: 'Right click attempt' },
    { value: 'shortcut_attempt', label: 'Shortcut attempt' },
    { value: 'camera_stopped', label: 'Camera stopped' },
    { value: 'screen_share_stopped', label: 'Screen share stopped' },
    { value: 'recording_missing', label: 'Recording missing' },
    { value: 'multiple_people', label: 'Multiple people' },
    { value: 'identity_mismatch', label: 'Identity mismatch' },
    { value: 'suspicious_audio_visual', label: 'Suspicious audio/visual' },
    { value: 'other', label: 'Other' },
];

function ProctoringReviewDecisionForm({
    testId,
    attemptId,
    review,
}: {
    testId: number;
    attemptId: number;
    review: ProctoringReviewDecision;
}) {
    const form = useForm<ProctoringReviewFormData>({
        status: review.status,
        risk_level: review.risk_level ?? '',
        reason_codes: review.reason_codes ?? [],
        notes: review.notes ?? '',
    });

    const toggleReasonCode = (reasonCode: string) => {
        form.setData(
            'reason_codes',
            form.data.reason_codes.includes(reasonCode)
                ? form.data.reason_codes.filter((value) => value !== reasonCode)
                : [...form.data.reason_codes, reasonCode],
        );
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.patch(
            route('admin.tests.results.proctoring-review.update', [
                testId,
                attemptId,
            ]),
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <section className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="border-b border-gray-200 px-6 py-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h4 className="text-base font-semibold text-gray-900">
                            Proctoring Review Decision
                        </h4>
                    </div>
                    <ReviewStatusBadge value={review.status} />
                </div>
            </div>

            <form onSubmit={submit} className="space-y-6 p-6">
                <div className="grid gap-4 lg:grid-cols-2">
                    <div>
                        <label
                            htmlFor="proctoring_review_status"
                            className="text-sm font-medium text-gray-700"
                        >
                            Review status
                        </label>
                        <select
                            id="proctoring_review_status"
                            value={form.data.status}
                            onChange={(event) =>
                                form.setData('status', event.target.value)
                            }
                            className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-900 focus:ring-gray-900"
                        >
                            {reviewStatusOptions.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                        <FormError message={form.errors.status} />
                    </div>

                    <div>
                        <label
                            htmlFor="proctoring_review_risk"
                            className="text-sm font-medium text-gray-700"
                        >
                            Risk level
                        </label>
                        <select
                            id="proctoring_review_risk"
                            value={form.data.risk_level}
                            onChange={(event) =>
                                form.setData('risk_level', event.target.value)
                            }
                            className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-900 focus:ring-gray-900"
                        >
                            {riskLevelOptions.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                        <FormError message={form.errors.risk_level} />
                    </div>
                </div>

                <div>
                    <p className="text-sm font-medium text-gray-700">
                        Reason codes
                    </p>
                    <div className="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        {reasonCodeOptions.map((option) => (
                            <label
                                key={option.value}
                                className="flex min-h-10 items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700"
                            >
                                <input
                                    type="checkbox"
                                    checked={form.data.reason_codes.includes(
                                        option.value,
                                    )}
                                    onChange={() =>
                                        toggleReasonCode(option.value)
                                    }
                                    className="rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                                />
                                <span>{option.label}</span>
                            </label>
                        ))}
                    </div>
                    <FormError message={form.errors.reason_codes} />
                </div>

                <div>
                    <label
                        htmlFor="proctoring_review_notes"
                        className="text-sm font-medium text-gray-700"
                    >
                        Notes
                    </label>
                    <textarea
                        id="proctoring_review_notes"
                        value={form.data.notes}
                        onChange={(event) =>
                            form.setData('notes', event.target.value)
                        }
                        rows={4}
                        className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-900 focus:ring-gray-900"
                    />
                    <FormError message={form.errors.notes} />
                </div>

                <div className="flex flex-wrap items-center justify-between gap-4 border-t border-gray-200 pt-4">
                    <div className="text-sm text-gray-600">
                        <div>
                            Reviewed by:{' '}
                            {review.reviewed_by
                                ? `${review.reviewed_by.name} (${review.reviewed_by.email})`
                                : 'Not reviewed yet'}
                        </div>
                        <div>
                            Reviewed at: {formatDateTime(review.reviewed_at)}
                        </div>
                    </div>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold uppercase tracking-wide text-white transition hover:bg-gray-800 disabled:opacity-50"
                    >
                        {form.processing ? 'Saving...' : 'Save review'}
                    </button>
                </div>
            </form>
        </section>
    );
}

function ProctoringRecordingReview({
    summary,
    cameraChunks,
    screenChunks,
}: {
    summary: ProctoringRecordingSummary;
    cameraChunks: Paginated<ProctoringRecordingChunk>;
    screenChunks: Paginated<ProctoringRecordingChunk>;
}) {
    const [selectedFolder, setSelectedFolder] = useState<
        'camera' | 'screen' | null
    >(null);
    const activeChunks = useMemo(() => {
        if (selectedFolder === 'camera') {
            return cameraChunks;
        }

        if (selectedFolder === 'screen') {
            return screenChunks;
        }

        return null;
    }, [cameraChunks, screenChunks, selectedFolder]);
    const activeMerged = selectedFolder
        ? recordingMerge(summary, selectedFolder)
        : null;
    const activeTitle =
        selectedFolder === 'camera'
            ? 'Camera Recording'
            : 'Screen Recording';

    return (
        <section className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div className="border-b border-gray-200 px-6 py-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h4 className="text-base font-semibold text-gray-900">
                            Recording Evidence
                        </h4>
                    </div>

                    {selectedFolder && (
                        <button
                            type="button"
                            onClick={() => setSelectedFolder(null)}
                            className="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Back to folders
                        </button>
                    )}
                </div>
            </div>

            <div className="space-y-6 p-6">
                {!selectedFolder && (
                    <div className="grid gap-4 md:grid-cols-2">
                        <RecordingFolderCard
                            title="Camera Recording"
                            status={summary.camera_status}
                            mergedStatus={summary.camera_merged_status}
                            onOpen={() => setSelectedFolder('camera')}
                        />
                        <RecordingFolderCard
                            title="Screen Recording"
                            status={summary.screen_status}
                            mergedStatus={summary.screen_merged_status}
                            onOpen={() => setSelectedFolder('screen')}
                        />
                    </div>
                )}

                {selectedFolder && activeChunks && (
                    <div className="space-y-4">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h5 className="text-sm font-semibold text-gray-900">
                                    {activeTitle} Folder
                                </h5>
                            </div>
                            <RecordingTypeBadge type={selectedFolder} />
                        </div>

                        {activeMerged?.url ? (
                            <MergedRecordingPlayer
                                title={activeTitle}
                                merged={activeMerged}
                            />
                        ) : activeMerged?.status === 'processing' ? (
                            <RecordingProcessingNotice />
                        ) : activeMerged?.status === 'failed' ? (
                            <RecordingMergeFailedNotice
                                error={activeMerged.error}
                            />
                        ) : null}

                        {!activeMerged?.url && (
                            activeChunks.data.length > 0 ? (
                                <>
                                    <div className="grid max-h-[720px] gap-4 overflow-y-auto pr-2 lg:grid-cols-2">
                                        {activeChunks.data.map((chunk) => (
                                            <RecordingChunkCard
                                                key={chunk.id}
                                                chunk={chunk}
                                            />
                                        ))}
                                    </div>
                                    <RecordingPagination chunks={activeChunks} />
                                </>
                            ) : (
                                <div className="rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                                    No {selectedFolder} recording chunks have
                                    been stored for this attempt.
                                </div>
                            )
                        )}
                    </div>
                )}
            </div>
        </section>
    );
}

function RecordingFolderCard({
    title,
    status,
    mergedStatus,
    onOpen,
}: {
    title: string;
    status: string;
    mergedStatus: string;
    onOpen: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onOpen}
            className="rounded-md border border-gray-200 bg-gray-50 p-4 text-left transition hover:border-gray-300 hover:bg-white hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
        >
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold text-gray-900">
                        {title}
                    </p>
                </div>
                <div className="flex flex-col items-end gap-1">
                    <StatusBadge value={status} />
                    <StatusBadge value={mergedStatus} />
                </div>
            </div>
        </button>
    );
}

function MergedRecordingPlayer({
    title,
    merged,
}: {
    title: string;
    merged: RecordingMerge;
}) {
    return (
        <article className="rounded-md border border-gray-200 bg-gray-50 p-3">
            <video
                src={merged.url ?? undefined}
                controls
                preload="metadata"
                className="aspect-video w-full rounded-md bg-gray-950"
            />
            <div className="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-600">
                <span className="font-medium">{title}</span>
                <span>
                    {merged.merged_at ? formatDateTime(merged.merged_at) : '-'}
                </span>
            </div>
        </article>
    );
}

function RecordingProcessingNotice() {
    return (
        <div className="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Recording is processing. Keep the queue worker running and refresh
            this page shortly.
        </div>
    );
}

function RecordingMergeFailedNotice({ error }: { error: string | null }) {
    return (
        <div className="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            Recording merge failed. The original chunks are shown below.
            {error && <p className="mt-1 text-xs">{error}</p>}
        </div>
    );
}

function RecordingChunkCard({
    chunk,
}: {
    chunk: ProctoringRecordingChunk;
}) {
    return (
        <article className="rounded-md border border-gray-200 bg-gray-50 p-3">
            <video
                src={chunk.url}
                controls
                preload="metadata"
                className="aspect-video w-full rounded-md bg-gray-950"
            />
            <p className="mt-2 text-xs font-medium text-gray-600">
                Video #{chunk.sequence}
            </p>
        </article>
    );
}

function RecordingPagination({
    chunks,
}: {
    chunks: Paginated<ProctoringRecordingChunk>;
}) {
    if (chunks.total === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 text-sm">
            <p className="text-gray-600">
                Showing {chunks.from ?? 0} to {chunks.to ?? 0} of{' '}
                {chunks.total} recording chunks
            </p>

            {chunks.last_page > 1 && (
                <div className="flex flex-wrap items-center gap-1">
                    {chunks.links.map((link, index) =>
                        link.url ? (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url}
                                preserveScroll
                                preserveState
                                className={
                                    'rounded-md border px-3 py-1.5 text-sm font-medium ' +
                                    (link.active
                                        ? 'border-gray-900 bg-gray-900 text-white'
                                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50')
                                }
                            >
                                {paginationLabel(link.label)}
                            </Link>
                        ) : (
                            <span
                                key={`${link.label}-${index}`}
                                className="rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm font-medium text-gray-400"
                            >
                                {paginationLabel(link.label)}
                            </span>
                        ),
                    )}
                </div>
            )}
        </div>
    );
}

function paginationLabel(label: string): string {
    return label
        .replace('&laquo;', '‹')
        .replace('&raquo;', '›')
        .replace('Previous', 'Prev')
        .trim();
}

type RecordingMerge = {
    status: string;
    url: string | null;
    merged_at: string | null;
    size_bytes: number;
    error: string | null;
};

function recordingMerge(
    summary: ProctoringRecordingSummary,
    type: 'camera' | 'screen',
): RecordingMerge {
    if (type === 'camera') {
        return {
            status: summary.camera_merged_status,
            url: summary.camera_merged_url,
            merged_at: summary.camera_merged_at,
            size_bytes: summary.camera_merged_size_bytes,
            error: summary.camera_merge_error,
        };
    }

    return {
        status: summary.screen_merged_status,
        url: summary.screen_merged_url,
        merged_at: summary.screen_merged_at,
        size_bytes: summary.screen_merged_size_bytes,
        error: summary.screen_merge_error,
    };
}

function MetadataDetails({
    metadata,
}: {
    metadata: Record<string, unknown>;
}) {
    const entries = Object.entries(metadata ?? {});

    if (entries.length === 0) {
        return <span>-</span>;
    }

    return (
        <dl className="space-y-1">
            {entries.map(([key, value]) => (
                <div key={key}>
                    <dt className="text-xs font-medium uppercase text-gray-500">
                        {formatLabel(key)}
                    </dt>
                    <dd className="break-words text-sm text-gray-900">
                        {formatFieldValue(value)}
                    </dd>
                </div>
            ))}
        </dl>
    );
}

function QuestionHeader({ answer, index }: { answer: Answer; index: number }) {
    return (
        <div className="flex flex-wrap items-start justify-between gap-4">
            <div className="max-w-4xl">
                <div className="flex flex-wrap items-center gap-2">
                    <p className="text-xs font-medium uppercase text-gray-500">
                        Question {index + 1}
                    </p>
                    <TypeBadge type={answer.type} />
                </div>
                <h5 className="mt-2 text-sm font-semibold text-gray-900">
                    {answer.question?.body ?? 'Question unavailable'}
                </h5>
                <p className="mt-1 text-xs text-gray-500">
                    Marks: {answer.question?.marks ?? 0}
                </p>
            </div>
            <div className="text-right text-sm">
                <AnswerBadge correct={answer.is_correct} />
                <div className="mt-1 text-xs text-gray-500">
                    Score: {answer.score}/{answer.question?.marks ?? 0}
                </div>
            </div>
        </div>
    );
}

function McqAnswer({ answer }: { answer: Answer }) {
    return (
        <div className="grid gap-4 md:grid-cols-2">
            <AnswerBlock label="Selected answer">
                {answer.selected_option?.body ?? 'No answer selected'}
            </AnswerBlock>
            <AnswerBlock label="Correct answer">
                {answer.correct_options.length > 0
                    ? answer.correct_options
                          .map((option) => option.body)
                          .join(', ')
                    : 'No correct option recorded'}
            </AnswerBlock>
        </div>
    );
}

function CodingAnswer({ answer }: { answer: Answer }) {
    const run = answer.execution_run;

    return (
        <div className="space-y-5">
            <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Detail label="Language">
                    {answer.language ?? 'Not recorded'}
                </Detail>
                <Detail label="Coding score">
                    {answer.score}/{answer.question?.marks ?? 0}
                </Detail>
                <Detail label="Run status">
                    {run ? <RunStatusBadge value={run.status} /> : 'No run'}
                </Detail>
                <Detail label="Run result">
                    {run ? <ResultBadge passed={run.passed} /> : 'No run'}
                </Detail>
                <Detail label="Visible cases">
                    {run ? summaryLabel(run.visible_summary) : 'No run'}
                </Detail>
                <Detail label="Hidden cases">
                    {run ? summaryLabel(run.hidden_summary) : 'No run'}
                </Detail>
                <Detail label="Run score">
                    {run
                        ? `${formatNullableNumber(
                              run.score_awarded,
                          )}/${formatNullableNumber(run.max_score)}`
                        : 'No run'}
                </Detail>
                <Detail label="Finished">
                    {formatDateTime(run?.finished_at)}
                </Detail>
            </dl>

            <div>
                <p className="text-xs font-medium uppercase text-gray-500">
                    Submitted code
                </p>
                <pre className="mt-2 max-h-96 overflow-auto rounded-md border border-gray-200 bg-gray-950 p-4 text-xs leading-6 text-gray-100">
                    <code>{answer.submitted_code ?? 'No code submitted'}</code>
                </pre>
            </div>

            {run?.error_message && (
                <div className="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {run.error_message}
                </div>
            )}

            {run ? (
                <TestCaseResultsTable results={run.test_case_results} />
            ) : (
                <div className="rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                    No final execution run has been recorded for this coding
                    answer yet.
                </div>
            )}
        </div>
    );
}

function TestCaseResultsTable({
    results,
}: {
    results: CodeExecutionTestCaseResult[];
}) {
    if (results.length === 0) {
        return (
            <div className="rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                No test case results were stored for this run.
            </div>
        );
    }

    return (
        <div className="overflow-x-auto rounded-md border border-gray-200">
            <table className="min-w-full divide-y divide-gray-200 text-sm">
                <thead className="bg-gray-50">
                    <tr>
                        {[
                            'Type',
                            'Status',
                            'Input',
                            'Expected',
                            'Actual',
                            'stdout',
                            'stderr',
                            'Compile',
                            'Message',
                            'Time',
                            'Memory',
                            'Provider',
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
                    {results.map((result) => (
                        <tr key={result.id}>
                            <td className="px-4 py-3 align-top">
                                <span
                                    className={
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-medium ' +
                                        (result.is_hidden
                                            ? 'bg-amber-100 text-amber-800'
                                            : 'bg-blue-100 text-blue-800')
                                    }
                                >
                                    {result.is_hidden ? 'Hidden' : 'Visible'}
                                </span>
                            </td>
                            <td className="px-4 py-3 align-top">
                                <div className="space-y-1">
                                    <RunStatusBadge value={result.status} />
                                    <TestCasePassedBadge
                                        passed={result.passed}
                                    />
                                </div>
                            </td>
                            <TableText value={result.input} />
                            <TableText value={result.expected_output} />
                            <TableText value={result.actual_output} />
                            <TableText value={result.stdout} />
                            <TableText value={result.stderr} />
                            <TableText value={result.compile_output} />
                            <TableText value={result.message} />
                            <TableText value={result.time} />
                            <TableText value={result.memory} />
                            <TableText
                                value={
                                    result.judge0_status_description ??
                                    result.judge0_status_id
                                }
                            />
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function TableText({ value }: { value: string | number | null }) {
    return (
        <td className="max-w-xs whitespace-pre-wrap break-words px-4 py-3 align-top text-gray-700">
            {formatNullableValue(value)}
        </td>
    );
}

function Metric({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <div>
            <dt className="text-sm font-medium text-gray-500">{label}</dt>
            <dd className="mt-1 text-sm font-semibold text-gray-900">
                {children}
            </dd>
        </div>
    );
}

function Detail({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <div>
            <dt className="text-sm font-medium text-gray-500">{label}</dt>
            <dd className="mt-1 break-words text-sm text-gray-900">
                {children}
            </dd>
        </div>
    );
}

function AnswerBlock({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <div className="rounded-md border border-gray-200 bg-gray-50 p-4">
            <p className="text-xs font-medium uppercase text-gray-500">
                {label}
            </p>
            <p className="mt-2 whitespace-pre-wrap text-sm text-gray-900">
                {children}
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

function FormError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-sm text-red-600">{message}</p>;
}

function TypeBadge({ type }: { type: string }) {
    return (
        <span
            className={
                'inline-flex rounded-full px-2.5 py-1 text-xs font-medium ' +
                (type === 'coding'
                    ? 'bg-indigo-100 text-indigo-700'
                    : 'bg-gray-100 text-gray-700')
            }
        >
            {type === 'coding' ? 'Coding' : 'MCQ'}
        </span>
    );
}

function RecordingTypeBadge({ type }: { type: string }) {
    return (
        <span
            className={
                'inline-flex rounded-full px-2.5 py-1 text-xs font-medium ' +
                (type === 'screen'
                    ? 'bg-blue-100 text-blue-700'
                    : 'bg-emerald-100 text-emerald-700')
            }
        >
            {type === 'screen' ? 'Screen' : 'Camera'}
        </span>
    );
}

function RunStatusBadge({ value }: { value: string }) {
    return (
        <span className="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
            {formatLabel(value)}
        </span>
    );
}

function SeverityBadge({ value }: { value: string }) {
    const className =
        value === 'high'
            ? 'bg-red-100 text-red-700'
            : value === 'medium'
              ? 'bg-amber-100 text-amber-700'
              : 'bg-emerald-100 text-emerald-700';

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

function AnswerBadge({ correct }: { correct: boolean }) {
    return (
        <span
            className={
                'inline-flex rounded-full px-2.5 py-1 text-xs font-medium ' +
                (correct
                    ? 'bg-green-100 text-green-700'
                    : 'bg-red-100 text-red-700')
            }
        >
            {correct ? 'Correct' : 'Incorrect'}
        </span>
    );
}

function TestCasePassedBadge({ passed }: { passed: boolean }) {
    return (
        <span
            className={
                'inline-flex rounded-full px-2.5 py-1 text-xs font-medium ' +
                (passed
                    ? 'bg-green-100 text-green-700'
                    : 'bg-red-100 text-red-700')
            }
        >
            {passed ? 'Pass' : 'Fail'}
        </span>
    );
}

function summaryLabel(summary: TestCaseSummary): string {
    return `${summary.passed}/${summary.total} passed`;
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

function formatFieldValue(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return 'Not provided';
    }

    if (Array.isArray(value)) {
        return value.join(', ');
    }

    if (typeof value === 'object') {
        return JSON.stringify(value);
    }

    return String(value);
}

function formatNullableNumber(value: number | null): string {
    return value === null ? '-' : String(value);
}

function formatNullableValue(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return String(value);
}
