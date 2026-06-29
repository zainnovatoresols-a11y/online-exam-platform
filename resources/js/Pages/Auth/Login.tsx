import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

// ─── Data ────────────────────────────────────────────────────────────────────

const accessNotes = [
    {
        title: 'Role-aware workspace',
        description:
            'Super admins, organization admins, solo admins, and candidates are redirected into the right area after sign-in.',
    },
    {
        title: 'Controlled candidate entry',
        description:
            'Candidates continue through invitation and public test flows instead of open account registration.',
    },
    {
        title: 'Review-ready operations',
        description:
            'Admins can manage tests, invitations, attempts, proctoring evidence, exports, and review decisions.',
    },
];

// ─── Component ───────────────────────────────────────────────────────────────

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title="Log in" />

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
                        <span className="text-xs text-zinc-500">Need access?</span>
                        <Link
                            href={route('onboarding.index')}
                            className="rounded-lg bg-emerald-500 px-3.5 py-1.5 text-xs font-bold text-black hover:bg-emerald-400 transition-colors"
                        >
                            Start onboarding
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
                                Admin Access
                            </span>
                        </div>

                        <h1 className="mb-3 text-3xl font-extrabold leading-[1.15] tracking-tight text-white sm:text-4xl">
                            Sign in to continue managing assessments.
                        </h1>

                        <p className="mx-auto max-w-[480px] text-sm leading-relaxed text-zinc-500">
                            Use your workspace account to create tests, invite candidates, review results, and inspect proctoring evidence from the correct role-based dashboard.
                        </p>
                    </div>
                </section>

                {/* ── Access notes strip (one line) ───────────────────────── */}
                <div
                    className="grid grid-cols-1 sm:grid-cols-3"
                    style={{
                        background: 'rgba(24,24,27,0.3)',
                        borderTop: '1px solid rgba(39,39,42,0.5)',
                        borderBottom: '1px solid rgba(39,39,42,0.5)',
                    }}
                >
                    {accessNotes.map((note, i) => (
                        <div
                            key={note.title}
                            className="px-5 py-6 text-center"
                            style={
                                i < accessNotes.length - 1
                                    ? { borderRight: '1px solid rgba(39,39,42,0.5)' }
                                    : {}
                            }
                        >
                            <div className="mx-auto mb-2.5 flex h-7 w-7 items-center justify-center rounded-full border border-zinc-700 text-xs font-bold text-emerald-400">
                                {i + 1}
                            </div>
                            <h3 className="mb-1 text-xs font-semibold text-white">{note.title}</h3>
                            <p className="text-[11px] leading-relaxed text-zinc-500">{note.description}</p>
                        </div>
                    ))}
                </div>

                {/* ── Login form (single column) ──────────────────────────── */}
                <div className="mx-auto max-w-lg px-6 py-12">
                    <div className="mb-5 rounded-xl border border-zinc-800 bg-zinc-900 p-5">
                        <p className="text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                            Secure access model
                        </p>
                        <p className="mt-2 text-xs leading-relaxed text-zinc-500">
                            Admin onboarding is separate from candidate access, so operational users and test takers do not enter through the same signup path.
                        </p>
                    </div>

                    <div className="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900">
                        <div className="border-b border-zinc-800 px-7 py-6">
                            <p className="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                                Workspace login
                            </p>
                            <h2 className="text-lg font-bold text-white">Welcome back</h2>
                            <p className="mt-1.5 text-xs leading-relaxed text-zinc-500">
                                Enter your email and password to open your assigned dashboard.
                            </p>
                        </div>

                        <div className="px-7 py-6">
                            {status && (
                                <div
                                    className="mb-5 rounded-lg border px-4 py-3 text-xs font-medium text-emerald-400"
                                    style={{ borderColor: 'rgba(16,185,129,0.2)', background: 'rgba(16,185,129,0.06)' }}
                                >
                                    {status}
                                </div>
                            )}

                            <form onSubmit={submit} className="space-y-5">
                                <div>
                                    <InputLabel htmlFor="email" value="Email" className="text-zinc-300" />

                                    <TextInput
                                        id="email"
                                        type="email"
                                        name="email"
                                        value={data.email}
                                        className="mt-1.5 block h-11 w-full rounded-lg border-zinc-700 bg-zinc-800 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-500 focus:ring-emerald-500"
                                        autoComplete="username"
                                        isFocused={true}
                                        onChange={(event) =>
                                            setData('email', event.target.value)
                                        }
                                        required
                                    />

                                    <InputError message={errors.email} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="password" value="Password" className="text-zinc-300" />

                                    <TextInput
                                        id="password"
                                        type="password"
                                        name="password"
                                        value={data.password}
                                        className="mt-1.5 block h-11 w-full rounded-lg border-zinc-700 bg-zinc-800 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-500 focus:ring-emerald-500"
                                        autoComplete="current-password"
                                        onChange={(event) =>
                                            setData('password', event.target.value)
                                        }
                                        required
                                    />

                                    <InputError
                                        message={errors.password}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <label className="flex items-center">
                                        <Checkbox
                                            name="remember"
                                            checked={data.remember}
                                            className="rounded border-zinc-700 bg-zinc-800 text-emerald-500 focus:ring-emerald-500"
                                            onChange={(event) =>
                                                setData(
                                                    'remember',
                                                    event.target.checked,
                                                )
                                            }
                                        />
                                        <span className="ms-2 text-xs text-zinc-500">
                                            Remember me
                                        </span>
                                    </label>

                                    {canResetPassword && (
                                        <Link
                                            href={route('password.request')}
                                            className="text-xs font-semibold text-emerald-400 underline underline-offset-4 hover:text-emerald-300"
                                        >
                                            Forgot password?
                                        </Link>
                                    )}
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex h-11 w-full items-center justify-center rounded-xl bg-emerald-500 px-4 text-sm font-bold text-black transition-colors hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    Log in
                                </button>
                            </form>
                        </div>
                    </div>

                    <div className="mt-5 rounded-2xl border border-zinc-800 bg-zinc-900 p-6">
                        <p className="text-sm font-semibold text-white">
                            Need admin access?
                        </p>
                        <p className="mt-2 text-xs leading-relaxed text-zinc-500">
                            Start with organization owner onboarding or create a
                            solo admin account for independent test operations.
                        </p>
                        <Link
                            href={route('onboarding.index')}
                            className="mt-4 inline-flex rounded-lg border border-zinc-700 px-4 py-2 text-xs font-semibold text-zinc-300 transition-colors hover:border-zinc-600 hover:text-white"
                        >
                            Start onboarding
                        </Link>
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