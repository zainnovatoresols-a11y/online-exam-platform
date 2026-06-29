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
            'Super admins, org admins, solo admins, and candidates are redirected into the right area after sign-in.',
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

            <div
                className="min-h-screen bg-zinc-950 text-zinc-100"
                style={{ fontFamily: "-apple-system, BlinkMacSystemFont, 'Inter', sans-serif" }}
            >
                {/* ── Sticky nav ──────────────────────────────────────────── */}
                <nav
                    className="sticky top-0 z-50 flex h-14 items-center justify-between border-b border-white/5 px-4 sm:px-8 backdrop-blur-md"
                    style={{ background: 'rgba(9,9,11,0.88)' }}
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
                        <span className="hidden text-xs text-zinc-500 sm:inline">Need access?</span>
                        <Link
                            href={route('onboarding.index')}
                            className="rounded-lg bg-emerald-500 px-3.5 py-1.5 text-xs font-bold text-black transition-colors hover:bg-emerald-400"
                        >
                            Start onboarding
                        </Link>
                    </div>
                </nav>

                {/* ── Two-column layout ───────────────────────────────────── */}
                {/*
                    KEY FIX: use grid with proportional cols so both sides fill
                    their space. 55/45 split gives the form a solid chunk.
                    No fixed pixel widths — percentages adapt to any viewport.
                */}
                <div className="flex min-h-[calc(100vh-56px)] flex-col lg:grid lg:grid-cols-[55%_45%]">

                    {/* ── Left: brand panel ───────────────────────────────── */}
                    {/*
                        KEY FIX: removed max-w-[500px] from inner div.
                        Content now fills the column. Padding creates breathing
                        room instead of a width cap.
                    */}
                    <div
                        className="relative hidden flex-col justify-center overflow-hidden lg:flex"
                        style={{ borderRight: '1px solid rgba(39,39,42,0.55)' }}
                    >
                        {/* Ambient glow */}
                        <div
                            className="pointer-events-none absolute -left-24 -top-24 h-[520px] w-[520px]"
                            style={{ background: 'radial-gradient(circle, rgba(16,185,129,0.09) 0%, transparent 68%)' }}
                        />

                        {/* Content — fills column with padding, NO max-w cap */}
                        <div className="relative w-full px-12 py-16 xl:px-20 2xl:px-28">

                            {/* Badge */}
                            <div
                                className="mb-8 inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5"
                                style={{ borderColor: 'rgba(16,185,129,0.2)', background: 'rgba(16,185,129,0.06)' }}
                            >
                                <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400" />
                                <span className="text-[10px] font-bold uppercase tracking-widest text-emerald-400">
                                    Admin Access
                                </span>
                            </div>

                            <h1 className="mb-5 text-3xl font-extrabold leading-[1.18] tracking-tight text-white xl:text-4xl">
                                Sign in to manage<br />your assessments.
                            </h1>

                            <p className="mb-12 max-w-[420px] text-sm leading-relaxed text-zinc-500">
                                Use your workspace credentials to create tests, invite candidates, review results, and inspect proctoring evidence from your role-based dashboard.
                            </p>

                            {/* Feature list */}
                            <div className="flex flex-col gap-6 max-w-[480px]">
                                {accessNotes.map((note, i) => (
                                    <div key={note.title} className="flex items-start gap-4">
                                        <div className="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-zinc-700 text-[11px] font-bold text-emerald-400">
                                            {i + 1}
                                        </div>
                                        <div>
                                            <p className="mb-1 text-[13px] font-semibold leading-snug text-white">
                                                {note.title}
                                            </p>
                                            <p className="text-xs leading-relaxed text-zinc-500">
                                                {note.description}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* ── Right: form panel ───────────────────────────────── */}
                    {/*
                        KEY FIX: removed fixed lg:w-[440px] — grid column
                        handles width (45%). Removed inner mx-auto/max-w-sm
                        wrappers that were shrinking the form. px fills edge-to-
                        edge with comfortable breathing room.
                    */}
                    <div
                        className="flex flex-col justify-center px-6 py-10 sm:px-10 xl:px-14"
                        style={{ background: 'rgba(15,15,17,0.7)' }}
                    >
                        {/* Secure access callout */}
                        <div className="mb-5 rounded-xl border border-zinc-800 bg-zinc-900 px-5 py-4">
                            <p className="mb-1.5 text-[10px] font-bold uppercase tracking-widest text-zinc-600">
                                Secure access model
                            </p>
                            <p className="text-[11px] leading-relaxed text-zinc-500">
                                Admin onboarding is separate from candidate access — operational users and test takers don't enter through the same path.
                            </p>
                        </div>

                        {/* Login card */}
                        <div className="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900">

                            {/* Card header */}
                            <div className="border-b border-zinc-800 px-6 py-5 sm:px-8 sm:py-6">
                                <p className="mb-1.5 text-[10px] font-bold uppercase tracking-widest text-zinc-600">
                                    Workspace login
                                </p>
                                <h2 className="text-xl font-bold text-white">Welcome back</h2>
                                <p className="mt-1.5 text-xs leading-relaxed text-zinc-500">
                                    Enter your credentials to open your assigned dashboard.
                                </p>
                            </div>

                            {/* Card body */}
                            <div className="px-6 py-6 sm:px-8">
                                {status && (
                                    <div
                                        className="mb-5 rounded-lg border px-4 py-3 text-xs font-medium text-emerald-400"
                                        style={{
                                            borderColor: 'rgba(16,185,129,0.2)',
                                            background: 'rgba(16,185,129,0.06)',
                                        }}
                                    >
                                        {status}
                                    </div>
                                )}

                                <form onSubmit={submit} className="space-y-5">

                                    {/* Email */}
                                    <div>
                                        <InputLabel
                                            htmlFor="email"
                                            value="Email address"
                                            className="mb-1.5 block text-xs font-medium text-zinc-300"
                                        />
                                        <TextInput
                                            id="email"
                                            type="email"
                                            name="email"
                                            value={data.email}
                                            className="block h-11 w-full rounded-lg border-zinc-700 bg-zinc-800 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                            autoComplete="username"
                                            isFocused={true}
                                            onChange={(e) => setData('email', e.target.value)}
                                            required
                                        />
                                        <InputError message={errors.email} className="mt-1.5 text-xs" />
                                    </div>

                                    {/* Password */}
                                    <div>
                                        <div className="mb-1.5 flex items-center justify-between">
                                            <InputLabel
                                                htmlFor="password"
                                                value="Password"
                                                className="block text-xs font-medium text-zinc-300"
                                            />
                                            {canResetPassword && (
                                                <Link
                                                    href={route('password.request')}
                                                    className="text-[11px] font-semibold text-emerald-400 underline underline-offset-[3px] hover:text-emerald-300"
                                                >
                                                    Forgot password?
                                                </Link>
                                            )}
                                        </div>
                                        <TextInput
                                            id="password"
                                            type="password"
                                            name="password"
                                            value={data.password}
                                            className="block h-11 w-full rounded-lg border-zinc-700 bg-zinc-800 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                            autoComplete="current-password"
                                            onChange={(e) => setData('password', e.target.value)}
                                            required
                                        />
                                        <InputError message={errors.password} className="mt-1.5 text-xs" />
                                    </div>

                                    {/* Remember me */}
                                    <label className="flex cursor-pointer items-center gap-2.5">
                                        <Checkbox
                                            name="remember"
                                            checked={data.remember}
                                            className="rounded border-zinc-700 bg-zinc-800 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-zinc-900"
                                            onChange={(e) => setData('remember', e.target.checked)}
                                        />
                                        <span className="text-xs text-zinc-500">Keep me signed in</span>
                                    </label>

                                    {/* Submit */}
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex h-11 w-full items-center justify-center rounded-xl bg-emerald-500 px-4 text-sm font-bold text-black transition-colors hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        {processing ? 'Signing in…' : 'Sign in'}
                                    </button>
                                </form>
                            </div>
                        </div>

                        {/* Onboarding nudge */}
                        <div className="mt-5 rounded-2xl border border-zinc-800 bg-zinc-900 px-6 py-5 sm:px-8">
                            <p className="text-sm font-semibold text-white">Need admin access?</p>
                            <p className="mt-1.5 text-xs leading-relaxed text-zinc-500">
                                Start with organization owner onboarding or create a solo admin account for independent test operations.
                            </p>
                            <Link
                                href={route('onboarding.index')}
                                className="mt-4 inline-flex items-center gap-1.5 rounded-lg border border-zinc-700 px-4 py-2 text-xs font-semibold text-zinc-300 transition-colors hover:border-zinc-600 hover:text-white"
                            >
                                Start onboarding
                                <svg className="h-3 w-3" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>

                        {/* Mobile-only feature list */}
                        <div className="mt-5 lg:hidden">
                            <div className="rounded-2xl border border-zinc-800 bg-zinc-900 px-6 py-5">
                                <p className="mb-4 text-[10px] font-bold uppercase tracking-widest text-zinc-600">
                                    What you can do
                                </p>
                                <div className="flex flex-col gap-4">
                                    {accessNotes.map((note, i) => (
                                        <div key={note.title} className="flex items-start gap-3">
                                            <div className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-zinc-700 text-[10px] font-bold text-emerald-400">
                                                {i + 1}
                                            </div>
                                            <div>
                                                <p className="mb-0.5 text-[12px] font-semibold text-white">{note.title}</p>
                                                <p className="text-[11px] leading-relaxed text-zinc-500">{note.description}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* ── Footer ──────────────────────────────────────────────── */}
                <footer
                    className="flex items-center justify-between px-4 py-4 sm:px-8 sm:py-5"
                    style={{ borderTop: '1px solid rgba(39,39,42,0.5)' }}
                >
                    <div className="flex items-center gap-2">
                        <div className="flex h-[22px] w-[22px] shrink-0 items-center justify-center rounded-md bg-emerald-500">
                            <svg className="h-[11px] w-[11px] text-black" fill="currentColor" viewBox="0 0 16 16">
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