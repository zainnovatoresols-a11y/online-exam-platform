import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

// ─── Data ────────────────────────────────────────────────────────────────────

const benefits = [
    'No organization setup is created with this path.',
    'You can start building solo tests immediately after sign-in.',
    'Best for one-admin hiring, training, or evaluation workflows.',
];

// ─── Component ───────────────────────────────────────────────────────────────

export default function SoloAdminRegister() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('onboarding.solo-admin.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Solo Admin Onboarding" />

            <div className="min-h-screen bg-zinc-950 text-zinc-100" style={{ fontFamily: "-apple-system, BlinkMacSystemFont, 'Inter', sans-serif" }}>

                {/* ── Sticky nav ──────────────────────────────────────────── */}
                <nav
                    className="sticky top-0 z-50 flex h-14 items-center justify-between border-b border-white/5 px-6 backdrop-blur-md"
                    style={{ background: 'rgba(9,9,11,0.85)' }}
                >
                    <Link href="/" className="flex items-center gap-2.5">
                        <div className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-500">
                            <svg className="h-3.5 w-3.5 text-black" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 1L1 5v6l7 4 7-4V5L8 1zm0 2.18L13.09 6 8 8.82 2.91 6 8 3.18zM2.5 7.27l5 2.87v4.04L2.5 11.3V7.27zm6.5 6.91V10.14l5-2.87v4.04l-5 2.87z" />
                            </svg>
                        </div>
                        <span className="text-sm font-semibold text-white">ExamPlatform</span>
                    </Link>

                    <div className="flex items-center gap-3">
                        <span className="text-xs text-zinc-500">Already have an account?</span>
                        <Link
                            href={route('login')}
                            className="rounded-lg bg-emerald-500 px-3.5 py-1.5 text-xs font-bold text-black hover:bg-emerald-400 transition-colors"
                        >
                            Log in
                        </Link>
                    </div>
                </nav>

                {/* ── Header ──────────────────────────────────────────────── */}
                <section className="relative overflow-hidden px-6 pb-10 pt-16 text-center">
                    <div
                        className="pointer-events-none absolute left-1/2 top-[-40px] h-[320px] w-[560px] -translate-x-1/2"
                        style={{ background: 'radial-gradient(circle, rgba(16,185,129,0.12) 0%, transparent 70%)' }}
                    />

                    <div className="relative">
                        <div
                            className="mb-6 inline-flex items-center gap-2 rounded-full border px-3.5 py-1"
                            style={{ borderColor: 'rgba(16,185,129,0.2)', background: 'rgba(16,185,129,0.06)' }}
                        >
                            <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400" />
                            <span className="text-[10px] font-semibold uppercase tracking-widest text-emerald-400">
                                Solo Setup
                            </span>
                        </div>

                        <h1 className="mb-3 text-3xl font-extrabold leading-[1.15] tracking-tight text-white sm:text-4xl">
                            Create a standalone admin account for independent test operations.
                        </h1>

                        <p className="mx-auto max-w-[480px] text-sm leading-relaxed text-zinc-500">
                            Use this path when one admin owns the assessment workflow and does not need an organization workspace.
                        </p>
                    </div>
                </section>

                {/* ── Benefits strip (one line) ───────────────────────────── */}
                <div
                    className="grid grid-cols-1 sm:grid-cols-3"
                    style={{
                        background: 'rgba(24,24,27,0.3)',
                        borderTop: '1px solid rgba(39,39,42,0.5)',
                        borderBottom: '1px solid rgba(39,39,42,0.5)',
                    }}
                >
                    {benefits.map((benefit, i) => (
                        <div
                            key={benefit}
                            className="px-5 py-6 text-center"
                            style={
                                i < benefits.length - 1
                                    ? { borderRight: '1px solid rgba(39,39,42,0.5)' }
                                    : {}
                            }
                        >
                            <div className="mx-auto mb-2.5 flex h-7 w-7 items-center justify-center rounded-full border border-zinc-700 text-xs font-bold text-emerald-400">
                                {i + 1}
                            </div>
                            <p className="text-[11px] leading-relaxed text-zinc-500">{benefit}</p>
                        </div>
                    ))}
                </div>

                {/* ── Onboarding form (single column) ─────────────────────── */}
                <div className="mx-auto max-w-2xl px-6 py-12">
                    <div className="mb-5 rounded-xl border border-zinc-800 bg-zinc-900 p-5">
                        <p className="text-sm font-semibold text-white">
                            Best fit
                        </p>
                        <p className="mt-2 text-xs leading-relaxed text-zinc-500">
                            Solo admin access works well for independent
                            recruiters, internal evaluators, trainers, and
                            small teams that do not need multi-admin
                            organization setup.
                        </p>
                    </div>

                    <div className="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900">
                        <div className="border-b border-zinc-800 px-7 py-6">
                            <p className="text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                                Solo admin access
                            </p>
                            <h2 className="mt-1.5 text-lg font-bold text-white">
                                Create your standalone admin account
                            </h2>
                            <p className="mt-1.5 text-xs leading-relaxed text-zinc-500">
                                This setup signs you in directly as an admin without
                                creating an organization record.
                            </p>
                        </div>

                        <div className="px-7 py-6">
                            <form onSubmit={submit} className="space-y-6">
                                <div className="space-y-4">
                                    <div>
                                        <h3 className="text-xs font-semibold uppercase tracking-widest text-zinc-500">
                                            Admin account
                                        </h3>
                                        <p className="mt-1 text-xs text-zinc-500">
                                            Use the details you want tied to your solo
                                            test operations.
                                        </p>
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="name" value="Name" className="text-zinc-300" />
                                        <TextInput
                                            id="name"
                                            value={data.name}
                                            className="mt-1.5 block h-11 w-full rounded-lg border-zinc-700 bg-zinc-800 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-500 focus:ring-emerald-500"
                                            autoComplete="name"
                                            placeholder="Ayesha Khan"
                                            isFocused={true}
                                            onChange={(event) =>
                                                setData('name', event.target.value)
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.name}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="email" value="Email" className="text-zinc-300" />
                                        <TextInput
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            className="mt-1.5 block h-11 w-full rounded-lg border-zinc-700 bg-zinc-800 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-500 focus:ring-emerald-500"
                                            autoComplete="email"
                                            placeholder="admin@example.com"
                                            onChange={(event) =>
                                                setData('email', event.target.value)
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.email}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <InputLabel
                                                htmlFor="password"
                                                value="Password"
                                                className="text-zinc-300"
                                            />
                                            <TextInput
                                                id="password"
                                                type="password"
                                                value={data.password}
                                                className="mt-1.5 block h-11 w-full rounded-lg border-zinc-700 bg-zinc-800 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-500 focus:ring-emerald-500"
                                                autoComplete="new-password"
                                                onChange={(event) =>
                                                    setData(
                                                        'password',
                                                        event.target.value,
                                                    )
                                                }
                                                required
                                            />
                                            <InputError
                                                message={errors.password}
                                                className="mt-2"
                                            />
                                        </div>

                                        <div>
                                            <InputLabel
                                                htmlFor="password_confirmation"
                                                value="Confirm password"
                                                className="text-zinc-300"
                                            />
                                            <TextInput
                                                id="password_confirmation"
                                                type="password"
                                                value={data.password_confirmation}
                                                className="mt-1.5 block h-11 w-full rounded-lg border-zinc-700 bg-zinc-800 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-500 focus:ring-emerald-500"
                                                autoComplete="new-password"
                                                onChange={(event) =>
                                                    setData(
                                                        'password_confirmation',
                                                        event.target.value,
                                                    )
                                                }
                                                required
                                            />
                                            <InputError
                                                message={
                                                    errors.password_confirmation
                                                }
                                                className="mt-2"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div className="flex flex-col gap-3 border-t border-zinc-800 pt-6 sm:flex-row sm:items-center sm:justify-between">
                                    <Link
                                        href={route('onboarding.index')}
                                        className="text-sm font-medium text-zinc-400 underline underline-offset-4 hover:text-zinc-200"
                                    >
                                        Back to account choices
                                    </Link>

                                    <PrimaryButton
                                        disabled={processing}
                                        className="w-full justify-center rounded-xl bg-emerald-500 text-black hover:bg-emerald-400 focus:bg-emerald-400 focus:ring-emerald-500 active:bg-emerald-500 sm:w-auto"
                                    >
                                        Create solo admin account
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {/* ── Footer ──────────────────────────────────────────────── */}
                <footer
                    className="flex items-center justify-between px-6 py-6"
                    style={{ borderTop: '1px solid rgba(39,39,42,0.5)' }}
                >
                    <div className="flex items-center gap-2.5">
                        <div className="flex h-[26px] w-[26px] shrink-0 items-center justify-center rounded-lg bg-emerald-500">
                            <svg className="h-3 w-3 text-black" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 1L1 5v6l7 4 7-4V5L8 1zm0 2.18L13.09 6 8 8.82 2.91 6 8 3.18zM2.5 7.27l5 2.87v4.04L2.5 11.3V7.27zm6.5 6.91V10.14l5-2.87v4.04l-5 2.87z" />
                            </svg>
                        </div>
                        <span className="text-sm font-semibold text-white">ExamPlatform</span>
                    </div>
                    <span className="text-[10px] text-zinc-700">© 2025 ExamPlatform. All rights reserved.</span>
                </footer>

            </div>
        </>
    );
}