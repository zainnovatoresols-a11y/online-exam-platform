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
    submitted_at: string | null;
    expires_at: string | null;
};

type Invitation = {
    id: number;
    starts_at: string | null;
};

const primaryActionClassName =
    'inline-flex h-11 w-full items-center justify-center rounded-xl border border-emerald-500 bg-emerald-500 px-5 text-sm font-semibold text-black transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-zinc-950 disabled:cursor-not-allowed disabled:border-zinc-700 disabled:bg-zinc-800 disabled:text-zinc-500 sm:w-auto';

export default function Show({
    test,
    invitation,
    attempt,
    server_now,
}: {
    test: Test;
    invitation: Invitation;
    attempt: Attempt | null;
    server_now: string;
}) {
    const [secondsUntilStart, setSecondsUntilStart] = useState(() =>
        secondsUntil(invitation.starts_at, server_now),
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
            theme="dark"
            header={
                <h2 className="text-xl font-semibold leading-tight text-zinc-100">
                    Test Landing
                </h2>
            }
        >
            <Head title={test.title} />

            <div className="bg-zinc-950 py-10">
                <div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/20">
                        <span
                            className={
                                'inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] ' +
                                statusBadgeClassName(test.status)
                            }
                        >
                            {formatLabel(test.status)}
                        </span>
                        <h1 className="mt-4 text-2xl font-semibold text-white">
                            {test.title}
                        </h1>

                        <dl className="mt-6 grid gap-3 sm:grid-cols-2">
                            <DetailStat
                                label="Organization"
                                value={test.organization?.name ?? 'Solo test'}
                            />
                            <DetailStat
                                label="Examiner"
                                value={test.creator?.name ?? 'Exam Admin'}
                            />
                            <DetailStat
                                label="Duration"
                                value={`${test.duration_minutes} minutes`}
                            />
                            <DetailStat
                                label="Pass percentage"
                                value={String(test.pass_mark)}
                            />
                            <DetailStat
                                label="Questions"
                                value={String(test.questions_count)}
                            />
                            <DetailStat
                                label="Start time"
                                value={
                                    invitation.starts_at
                                        ? formatDateTime(invitation.starts_at)
                                        : 'Available now'
                                }
                            />
                        </dl>

                        <div className="mt-6 rounded-xl border border-zinc-800 bg-zinc-950/70 p-4">
                            {attempt?.status === 'submitted' ? (
                                <div className="flex flex-wrap items-center justify-between gap-4">
                                    <div>
                                        <p className="text-sm font-medium text-white">
                                            Submitted
                                        </p>
                                        <p className="mt-1 text-sm leading-6 text-zinc-400">
                                            Your assessment has been submitted.
                                            HR will contact you if your profile
                                            meets the requirements.
                                        </p>
                                    </div>
                                    <Link
                                        href={route(
                                            'candidate.attempts.show',
                                            attempt.id,
                                        )}
                                        className={primaryActionClassName}
                                    >
                                        View submission
                                    </Link>
                                </div>
                            ) : attempt ? (
                                <div className="flex flex-wrap items-center justify-between gap-4">
                                    <div>
                                        <p className="text-sm font-medium text-white">
                                            Attempt started
                                        </p>
                                        <p className="mt-1 text-sm leading-6 text-zinc-400">
                                            Continue your test from where you
                                            left it.
                                        </p>
                                    </div>
                                    <Link
                                        href={route(
                                            'candidate.attempts.show',
                                            attempt.id,
                                        )}
                                        className={primaryActionClassName}
                                    >
                                        Resume test
                                    </Link>
                                </div>
                            ) : (
                                <div className="flex flex-wrap items-center justify-between gap-4">
                                    <div>
                                        <p className="text-sm font-medium text-white">
                                            Ready to begin
                                        </p>
                                        <p className="mt-1 text-sm leading-6 text-zinc-400">
                                            {!isPublished
                                                ? test.status === 'closed'
                                                    ? 'This test is closed and cannot be started.'
                                                    : 'This test is not published yet. Please wait for the examiner.'
                                                : !hasStarted
                                                  ? `Test starts in ${startCountdown}.`
                                                  : test.questions_count === 0
                                                    ? 'This test has no questions yet. Please contact the examiner.'
                                                    : 'You will see the assessment questions after starting the test.'}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={startAttempt}
                                        disabled={!canStart}
                                        className={primaryActionClassName}
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

function DetailStat({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border border-zinc-800 bg-zinc-950/70 p-4">
            <dt className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                {label}
            </dt>
            <dd className="mt-2 text-sm font-semibold text-zinc-100">{value}</dd>
        </div>
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

function statusBadgeClassName(status: string): string {
    const normalizedStatus = status.toLowerCase();

    if (
        normalizedStatus.includes('published') ||
        normalizedStatus.includes('open') ||
        normalizedStatus.includes('active')
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
