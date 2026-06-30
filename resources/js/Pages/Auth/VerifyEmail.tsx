import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const primaryButtonClassName =
    'inline-flex h-11 w-full items-center justify-center rounded-xl bg-emerald-500 px-5 text-sm font-bold text-black transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:ring-offset-2 focus:ring-offset-zinc-950 disabled:opacity-60 sm:w-auto';
const secondaryButtonClassName =
    'inline-flex h-11 w-full items-center justify-center rounded-xl border border-zinc-700 bg-zinc-950 px-5 text-sm font-bold text-zinc-300 transition hover:border-zinc-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-zinc-500/40 focus:ring-offset-2 focus:ring-offset-zinc-950 sm:w-auto';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout theme="dark">
            <Head title="Email Verification" />

            <div className="space-y-6">
                <header>
                    <p className="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-300">
                        Email verification
                    </p>
                    <h1 className="mt-3 text-2xl font-semibold text-white">
                        Verify your email
                    </h1>
                    <p className="mt-3 text-sm leading-6 text-zinc-400">
                        Thanks for signing up. Before getting started, please
                        verify your email address by clicking the link we just
                        emailed to you.
                    </p>
                </header>

                {status === 'verification-link-sent' && (
                    <div className="rounded-xl border border-emerald-400/20 bg-emerald-400/10 p-4 text-sm font-medium text-emerald-200">
                        A new verification link has been sent to the email
                        address you provided during registration.
                    </div>
                )}

                <form onSubmit={submit}>
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <button
                            type="submit"
                            className={primaryButtonClassName}
                            disabled={processing}
                        >
                            Resend Verification Email
                        </button>

                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className={secondaryButtonClassName}
                        >
                            Log Out
                        </Link>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
