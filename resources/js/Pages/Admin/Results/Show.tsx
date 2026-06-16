import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

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

type Answer = {
    id: number;
    question: {
        id: number;
        body: string;
        marks: number;
        order: number;
    } | null;
    selected_option: {
        id: number;
        body: string;
        is_correct: boolean;
    } | null;
    correct_options: {
        id: number;
        body: string;
    }[];
    is_correct: boolean;
    score: number;
};

type Props = {
    test: Test;
    invitation: Invitation | null;
    candidate: Candidate;
    attempt: Attempt;
    answers: Answer[];
};

export default function Show({
    test,
    invitation,
    candidate,
    attempt,
    answers,
}: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Attempt Details
                </h2>
            }
        >
            <Head title={`${candidate.name ?? 'Candidate'} Result`} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
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
                                <p className="mt-3 text-sm font-medium uppercase text-gray-500">
                                    {test.status}
                                </p>
                                <h3 className="mt-2 text-lg font-semibold text-gray-900">
                                    {test.title}
                                </h3>
                                <p className="mt-2 text-sm text-gray-600">
                                    {candidate.name ?? 'Unnamed candidate'} ·{' '}
                                    {candidate.email ?? 'No email'}
                                </p>
                            </div>

                            <div className="grid min-w-72 grid-cols-2 gap-4 text-sm">
                                <Metric label="Score">
                                    {attempt.score}/{attempt.max_score}
                                </Metric>
                                <Metric label="Percentage">
                                    {attempt.percentage === null
                                        ? 'Pending'
                                        : `${attempt.percentage.toFixed(2)}%`}
                                </Metric>
                                <Metric label="Result">
                                    <ResultBadge passed={attempt.passed} />
                                </Metric>
                                <Metric label="Attempt">
                                    <StatusBadge value={attempt.status} />
                                </Metric>
                            </div>
                        </div>
                    </div>

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

                    <section className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="border-b border-gray-200 px-6 py-4">
                            <h4 className="text-base font-semibold text-gray-900">
                                MCQ Answers
                            </h4>
                        </div>
                        <div className="divide-y divide-gray-200">
                            {answers.map((answer, index) => (
                                <article
                                    key={answer.id}
                                    className="space-y-4 px-6 py-5"
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-4">
                                        <div className="max-w-3xl">
                                            <p className="text-xs font-medium uppercase text-gray-500">
                                                Question {index + 1}
                                            </p>
                                            <h5 className="mt-1 text-sm font-semibold text-gray-900">
                                                {answer.question?.body ??
                                                    'Question unavailable'}
                                            </h5>
                                            <p className="mt-1 text-xs text-gray-500">
                                                Marks:{' '}
                                                {answer.question?.marks ?? 0}
                                            </p>
                                        </div>
                                        <div className="text-right text-sm">
                                            <AnswerBadge
                                                correct={answer.is_correct}
                                            />
                                            <div className="mt-1 text-xs text-gray-500">
                                                Score: {answer.score}/
                                                {answer.question?.marks ?? 0}
                                            </div>
                                        </div>
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <AnswerBlock label="Selected answer">
                                            {answer.selected_option?.body ??
                                                'No answer selected'}
                                        </AnswerBlock>
                                        <AnswerBlock label="Correct answer">
                                            {answer.correct_options.length > 0
                                                ? answer.correct_options
                                                      .map(
                                                          (option) =>
                                                              option.body,
                                                      )
                                                      .join(', ')
                                                : 'No correct option recorded'}
                                        </AnswerBlock>
                                    </div>
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
                </div>
            </div>
        </AuthenticatedLayout>
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
            <p className="mt-2 text-sm text-gray-900">{children}</p>
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
