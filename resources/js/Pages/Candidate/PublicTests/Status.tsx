import GuestLayout from '@/Layouts/GuestLayout';
import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

type Test = {
    title: string;
    organization: { id: number; name: string } | null;
    creator: { id: number; name: string; email: string } | null;
};

type Props = {
    status: string;
    message: string;
    test: Test | null;
    available_at?: string | null;
    server_now?: string | null;
    action_url?: string | null;
    action_label?: string | null;
};

export default function Status({
    status,
    message,
    test,
    available_at,
    server_now,
    action_url,
    action_label = 'Continue',
}: Props) {
    const [secondsUntilStart, setSecondsUntilStart] = useState(() =>
        secondsUntil(available_at ?? null, server_now ?? null),
    );
    const hasCountdown = Boolean(available_at && action_url);
    const isReady = secondsUntilStart <= 0;
    const countdown = useMemo(
        () => formatRemainingTime(secondsUntilStart),
        [secondsUntilStart],
    );

    useEffect(() => {
        if (!hasCountdown || isReady) {
            return;
        }

        const timer = window.setInterval(() => {
            setSecondsUntilStart((seconds) => Math.max(seconds - 1, 0));
        }, 1000);

        return () => window.clearInterval(timer);
    }, [hasCountdown, isReady]);

    useEffect(() => {
        if (hasCountdown && isReady && action_url) {
            router.visit(action_url);
        }
    }, [action_url, hasCountdown, isReady]);

    return (
        <GuestLayout theme="dark">
            <Head title="Test Status" />

            <div className="space-y-5">
                <p
                    className={
                        'inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] ' +
                        statusBadgeClassName(status)
                    }
                >
                    {status.replace('_', ' ')}
                </p>
                <h1 className="text-2xl font-semibold text-white">
                    {message}
                </h1>
                <p className="text-sm leading-6 text-zinc-400">
                    {test
                        ? `${test.title} from ${
                              test.organization?.name ??
                              test.creator?.name ??
                              'Exam Admin'
                          } is not available to candidates right now.`
                        : 'Please check the link you received from the test administrator.'}
                </p>

                {hasCountdown && (
                    <div className="rounded-xl border border-zinc-800 bg-zinc-950/70 p-4">
                        <p className="text-sm font-medium text-zinc-300">
                            Starts in
                        </p>
                        <p className="mt-2 text-3xl font-semibold text-emerald-300">
                            {countdown}
                        </p>
                    </div>
                )}

                {hasCountdown && action_url && (
                    <button
                        type="button"
                        onClick={() => router.visit(action_url)}
                        disabled={!isReady}
                        className="inline-flex h-11 w-full items-center justify-center rounded-xl border border-emerald-500 bg-emerald-500 px-5 text-xs font-semibold uppercase tracking-widest text-black transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-zinc-950 disabled:cursor-not-allowed disabled:border-zinc-700 disabled:bg-zinc-800 disabled:text-zinc-500 sm:w-auto"
                    >
                        {action_label}
                    </button>
                )}
            </div>
        </GuestLayout>
    );
}

function statusBadgeClassName(status: string): string {
    const normalizedStatus = status.toLowerCase();

    if (
        normalizedStatus.includes('expired') ||
        normalizedStatus.includes('invalid') ||
        normalizedStatus.includes('closed') ||
        normalizedStatus.includes('error')
    ) {
        return 'border-red-400/20 bg-red-400/10 text-red-200';
    }

    if (
        normalizedStatus.includes('pending') ||
        normalizedStatus.includes('waiting') ||
        normalizedStatus.includes('not') ||
        normalizedStatus.includes('scheduled')
    ) {
        return 'border-amber-400/20 bg-amber-400/10 text-amber-200';
    }

    return 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200';
}

function secondsUntil(target: string | null, serverNow: string | null): number {
    if (!target || !serverNow) {
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
