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
        <GuestLayout>
            <Head title="Test Status" />

            <div className="space-y-4">
                <p className="text-sm font-medium uppercase text-gray-500">
                    {status.replace('_', ' ')}
                </p>
                <h1 className="text-xl font-semibold text-gray-900">
                    {message}
                </h1>
                <p className="text-sm text-gray-600">
                    {test
                        ? `${test.title} from ${
                              test.organization?.name ??
                              test.creator?.name ??
                              'Exam Admin'
                          } is not available to candidates right now.`
                        : 'Please check the link you received from the test administrator.'}
                </p>

                {hasCountdown && (
                    <div className="rounded-md bg-gray-50 p-4">
                        <p className="text-sm font-medium text-gray-900">
                            Starts in
                        </p>
                        <p className="mt-2 text-2xl font-semibold text-gray-900">
                            {countdown}
                        </p>
                    </div>
                )}

                {hasCountdown && action_url && (
                    <button
                        type="button"
                        onClick={() => router.visit(action_url)}
                        disabled={!isReady}
                        className="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-gray-900 disabled:cursor-not-allowed disabled:bg-gray-400"
                    >
                        {action_label}
                    </button>
                )}
            </div>
        </GuestLayout>
    );
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
