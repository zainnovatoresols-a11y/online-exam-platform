import GuestLayout from '@/Layouts/GuestLayout';
import { Head } from '@inertiajs/react';

type Invitation = {
    email: string;
    test: {
        id: number;
        title: string;
    };
} | null;

type Props = {
    status: string;
    message: string;
    invitation: Invitation;
};

export default function Status({ status, message, invitation }: Props) {
    return (
        <GuestLayout theme="dark">
            <Head title="Invitation Status" />

            <div className="space-y-5">
                <p
                    className={
                        'inline-flex rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] ' +
                        statusBadgeClassName(status)
                    }
                >
                    {status}
                </p>
                <h1 className="text-2xl font-semibold text-white">
                    {message}
                </h1>

                {invitation && (
                    <div className="rounded-xl border border-zinc-800 bg-zinc-950/70 p-4 text-sm text-zinc-300">
                        <span className="font-medium text-white">
                            {invitation.test.title}
                        </span>{' '}
                        was sent to {invitation.email}.
                    </div>
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
        normalizedStatus.includes('error')
    ) {
        return 'border-red-400/20 bg-red-400/10 text-red-200';
    }

    if (
        normalizedStatus.includes('pending') ||
        normalizedStatus.includes('waiting') ||
        normalizedStatus.includes('not')
    ) {
        return 'border-amber-400/20 bg-amber-400/10 text-amber-200';
    }

    return 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200';
}
