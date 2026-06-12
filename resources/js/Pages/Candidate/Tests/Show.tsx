import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

type Test = {
    id: number;
    title: string;
    duration_minutes: number;
    pass_mark: number;
    starts_at: string | null;
    status: string;
    questions_count: number;
    organization: { id: number; name: string } | null;
    creator: { id: number; name: string; email: string } | null;
};

type Attempt = {
    id: number;
    status: string;
    score: number;
    max_score: number;
    total_marks: number;
    percentage: string | number | null;
    passed: boolean | null;
    submitted_at: string | null;
    expires_at: string | null;
};

export default function Show({
    test,
    attempt,
    server_now,
}: {
    test: Test;
    attempt: Attempt | null;
    server_now: string;
}) {
    const [secondsUntilStart, setSecondsUntilStart] = useState(() =>
        secondsUntil(test.starts_at, server_now),
    );
    const isPublished = test.status === 'published';
    const hasStarted = secondsUntilStart <= 0;
    const canStart = isPublished && hasStarted && test.questions_count > 0;
    const startCountdown = useMemo(
        () => formatRemainingTime(secondsUntilStart),
        [secondsUntilStart],
    );

    useEffect(() => {
        if (hasStarted) {
            return;
        }

        const timer = window.setInterval(() => {
            setSecondsUntilStart((seconds) => Math.max(seconds - 1, 0));
        }, 1000);

        return () => window.clearInterval(timer);
    }, [hasStarted]);

    const startAttempt = () => {
        router.post(route('candidate.tests.attempts.store', test.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Test Landing
                </h2>
            }
        >
            <Head title={test.title} />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <p className="text-sm font-medium uppercase text-gray-500">
                            {test.status}
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold text-gray-900">
                            {test.title}
                        </h1>

                        <dl className="mt-6 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Organization
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {test.organization?.name ?? 'Solo test'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Examiner
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {test.creator?.name ?? 'Exam Admin'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Duration
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {test.duration_minutes} minutes
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Pass percentage
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {test.pass_mark}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    MCQ questions
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {test.questions_count}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Start time
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {test.starts_at
                                        ? formatDateTime(test.starts_at)
                                        : 'Available now'}
                                </dd>
                            </div>
                        </dl>

                        <div className="mt-6 rounded-md bg-gray-50 p-4">
                            {attempt?.status === 'submitted' ? (
                                <div className="flex flex-wrap items-center justify-between gap-4">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">
                                            Submitted
                                        </p>
                                        <p className="mt-1 text-sm text-gray-600">
                                            Score: {attempt.score} /{' '}
                                            {attempt.max_score ||
                                                attempt.total_marks}
                                        </p>
                                    </div>
                                    <Link
                                        href={route(
                                            'candidate.attempts.show',
                                            attempt.id,
                                        )}
                                        className="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white"
                                    >
                                        View result
                                    </Link>
                                </div>
                            ) : attempt ? (
                                <div className="flex flex-wrap items-center justify-between gap-4">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">
                                            Attempt started
                                        </p>
                                        <p className="mt-1 text-sm text-gray-600">
                                            Continue your MCQ test from where
                                            you left it.
                                        </p>
                                    </div>
                                    <Link
                                        href={route(
                                            'candidate.attempts.show',
                                            attempt.id,
                                        )}
                                        className="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white"
                                    >
                                        Resume test
                                    </Link>
                                </div>
                            ) : (
                                <div className="flex flex-wrap items-center justify-between gap-4">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">
                                            Ready to begin
                                        </p>
                                        <p className="mt-1 text-sm text-gray-600">
                                            {!isPublished
                                                ? test.status === 'closed'
                                                    ? 'This test is closed and cannot be started.'
                                                    : 'This test is not published yet. Please wait for the examiner.'
                                                : !hasStarted
                                                ? `Test starts in ${startCountdown}.`
                                                : test.questions_count === 0
                                                ? 'This test has no MCQ questions yet. Please contact the examiner.'
                                                : 'You will see all MCQ questions after starting the test.'}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={startAttempt}
                                        disabled={!canStart}
                                        className="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:bg-gray-400"
                                    >
                                        Start test
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function secondsUntil(target: string | null, serverNow: string): number {
    if (!target) {
        return 0;
    }

    return Math.max(
        Math.floor(
            (new Date(target).getTime() - new Date(serverNow).getTime()) /
                1000,
        ),
        0,
    );
}

function formatRemainingTime(totalSeconds: number): string {
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    if (days > 0) {
        return `${days}d ${hours}h ${minutes}m`;
    }

    if (hours > 0) {
        return `${hours}h ${minutes}m ${seconds}s`;
    }

    return `${minutes}m ${seconds}s`;
}

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
