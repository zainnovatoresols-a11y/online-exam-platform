import { Head, Link } from '@inertiajs/react';

// ─── Data ────────────────────────────────────────────────────────────────────

const onboardingNotes = [
    'Admin onboarding only. Candidate access is created from invitations and public test registration after a test is published.',
    'Organization owners can create their workspace first, then add admins inside that organization.',
    'Solo admins skip organization setup and start directly with standalone test operations.',
];

const orgOwnerFeatures = [
    {
        title: 'Team workspace',
        detail: 'One parent organization holds all tests, results, and admin accounts under a single ownership structure.',
    },
    {
        title: 'Scoped admin roles',
        detail: 'Add admins after initial setup who operate independently inside your organization.',
    },
    {
        title: 'Centralized ownership',
        detail: 'The owner dashboard gives full visibility and lifecycle control over the entire organization.',
    },
];

const soloAdminFeatures = [
    {
        title: 'Direct account access',
        detail: 'No organization layer required — land straight into the test creation and management dashboard.',
    },
    {
        title: 'Full test lifecycle',
        detail: 'Create, publish, invite candidates, and review results entirely from one standalone account.',
    },
    {
        title: 'Flexible operations',
        detail: 'Build and deliver assessments without any team hierarchy or organization approval.',
    },
];

// ─── Shared sub-components ───────────────────────────────────────────────────

function FeatureItem({ title, detail, emerald }: { title: string; detail: string; emerald?: boolean }) {
    return (
        <div className="flex gap-3">
            <div
                className="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border"
                style={
                    emerald
                        ? { borderColor: 'rgba(16,185,129,0.25)', background: 'rgba(16,185,129,0.07)' }
                        : { borderColor: 'rgba(63,63,70,0.8)', background: 'rgba(39,39,42,0.4)' }
                }
            >
                <span
                    className="h-1.5 w-1.5 rounded-full"
                    style={{ background: emerald ? '#34d399' : '#52525b' }}
                />
            </div>
            <div>
                <p className="text-xs font-semibold text-zinc-200">{title}</p>
                <p className="mt-0.5 text-[11px] leading-relaxed text-zinc-500">{detail}</p>
            </div>
        </div>
    );
}

// ─── Component ───────────────────────────────────────────────────────────────

export default function Index() {
    return (
        <>
            <Head title="Admin Onboarding" />

            <div
                className="min-h-screen bg-zinc-950 text-zinc-100"
                style={{ fontFamily: "-apple-system, BlinkMacSystemFont, 'Inter', sans-serif" }}
            >
                {/* ── Sticky nav ──────────────────────────────────────────── */}
                <nav
                    className="sticky top-0 z-50 flex h-14 items-center justify-between border-b border-white/5 px-6 backdrop-blur-md"
                    style={{ background: 'rgba(9,9,11,0.85)' }}
                >
                    <div className="flex items-center gap-2.5">
                        <div className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-500">
                            <svg className="h-3.5 w-3.5 text-black" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 1L1 5v6l7 4 7-4V5L8 1zm0 2.18L13.09 6 8 8.82 2.91 6 8 3.18zM2.5 7.27l5 2.87v4.04L2.5 11.3V7.27zm6.5 6.91V10.14l5-2.87v4.04l-5 2.87z" />
                            </svg>
                        </div>
                        <span className="text-sm font-semibold text-white">ExamPlatform</span>
                    </div>
                    <Link
                        href={route('login')}
                        className="text-xs text-zinc-500 transition-colors hover:text-zinc-300"
                    >
                        Log in
                    </Link>
                </nav>

                {/* ── Page body ────────────────────────────────────────────── */}
                <div className="mx-auto max-w-5xl px-6 py-16">

                    {/* ── Page header ────────────────────────────────────── */}
                    <div className="mb-10">
                        <div
                            className="mb-4 inline-flex items-center gap-2 rounded-full border px-3.5 py-1"
                            style={{ borderColor: 'rgba(16,185,129,0.2)', background: 'rgba(16,185,129,0.06)' }}
                        >
                            <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400" />
                            <span className="text-[10px] font-semibold uppercase tracking-widest text-emerald-400">
                                Admin Onboarding
                            </span>
                        </div>
                        <h1 className="mb-3 max-w-xl text-3xl font-bold leading-tight tracking-tight text-white">
                            Choose the admin setup that matches your assessment operations.
                        </h1>
                        <p className="max-w-lg text-sm leading-relaxed text-zinc-500">
                            Both paths lead into the same testing platform, but they start with different ownership models. Pick the one that fits how your team will create tests and manage results.
                        </p>
                    </div>

                    {/* ── Candidate access info — full width ─────────────── */}
                    <div className="mb-12 flex gap-4 rounded-xl border border-zinc-800 bg-zinc-900 p-5">
                        <div
                            className="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border"
                            style={{ borderColor: 'rgba(16,185,129,0.2)', background: 'rgba(16,185,129,0.06)' }}
                        >
                            <svg className="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div>
                            <p className="mb-1 text-sm font-semibold text-white">
                                Candidate access remains controlled
                            </p>
                            <p className="text-xs leading-relaxed text-zinc-500">
                                {onboardingNotes[0]}
                            </p>
                        </div>
                    </div>

                    {/* ══ Row 1 — Organization owner ══════════════════════════ */}
                    <div className="grid gap-8 lg:grid-cols-[1fr_1.1fr] lg:items-start">

                        {/* Left: org owner context */}
                        <div className="space-y-7">
                            <div>
                                <p className="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                                    For Teams
                                </p>
                                <h2 className="mb-2 text-xl font-bold leading-snug tracking-tight text-white">
                                    Built for multi-admin operations
                                </h2>
                                <p className="text-xs leading-relaxed text-zinc-500">
                                    {onboardingNotes[1]}
                                </p>
                            </div>

                            <div className="space-y-4">
                                {orgOwnerFeatures.map((f) => (
                                    <FeatureItem key={f.title} title={f.title} detail={f.detail} emerald />
                                ))}
                            </div>

                            {/* Tip */}
                            <div
                                className="rounded-lg border px-4 py-3"
                                style={{ borderColor: 'rgba(16,185,129,0.15)', background: 'rgba(16,185,129,0.04)' }}
                            >
                                <p className="text-[11px] leading-relaxed text-zinc-500">
                                    <span className="font-semibold text-emerald-400">Tip —</span>{' '}
                                    Organization setup takes about 2 minutes. Admins and tests can be added at any point after the workspace is created.
                                </p>
                            </div>
                        </div>

                        {/* Right: org owner card */}
                        <Link
                            href={route('onboarding.organization-owner.create')}
                            className="flex flex-col rounded-[18px] bg-zinc-900 p-6 transition-opacity hover:opacity-90"
                            style={{
                                border: '1px solid rgba(16,185,129,0.3)',
                                boxShadow: '0 0 60px -20px rgba(16,185,129,0.15)',
                            }}
                        >
                            <div className="mb-2.5 flex items-start justify-between gap-2">
                                <span className="text-[10px] font-semibold uppercase tracking-widest text-emerald-400">
                                    Shared Ownership
                                </span>
                                <span className="whitespace-nowrap rounded-full bg-emerald-500 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-black">
                                    For Teams
                                </span>
                            </div>

                            <h3 className="mb-2 text-[17px] font-bold text-white">Organization owner</h3>

                            <p className="mb-4 text-xs leading-relaxed text-zinc-500">
                                Create the organization workspace and land in the owner dashboard with access to manage your own organization and add admins.
                            </p>

                            <ul className="mb-4 space-y-2.5">
                                {[
                                    'Creates the organization and owner account together',
                                    'Lets you add admins after setup',
                                    'Keeps all organization tests under one owner structure',
                                ].map((item) => (
                                    <li key={item} className="flex items-start gap-2.5 text-xs text-zinc-300">
                                        <span
                                            className="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-[9px] text-emerald-400"
                                            style={{ background: 'rgba(16,185,129,0.12)' }}
                                        >
                                            ✓
                                        </span>
                                        {item}
                                    </li>
                                ))}
                            </ul>

                            <p className="mb-5 flex-1 text-[10px] text-zinc-600">
                                Recommended when multiple admins will work under one organization.
                            </p>

                            <span className="mt-auto block rounded-xl bg-emerald-500 py-3 text-center text-xs font-bold text-black">
                                Continue as organization owner
                            </span>
                        </Link>
                    </div>

                    {/* ── Divider ─────────────────────────────────────────── */}
                    <div
                        className="my-10"
                        style={{ borderTop: '1px solid rgba(39,39,42,0.5)' }}
                    />

                    {/* ══ Row 2 — Solo admin ═══════════════════════════════════ */}
                    <div className="grid gap-8 lg:grid-cols-[1fr_1.1fr] lg:items-start">

                        {/* Left: solo admin context */}
                        <div className="space-y-7">
                            <div>
                                <p className="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                                    Independent Use
                                </p>
                                <h2 className="mb-2 text-xl font-bold leading-snug tracking-tight text-white">
                                    No overhead, direct control
                                </h2>
                                <p className="text-xs leading-relaxed text-zinc-500">
                                    {onboardingNotes[2]}
                                </p>
                            </div>

                            <div className="space-y-4">
                                {soloAdminFeatures.map((f) => (
                                    <FeatureItem key={f.title} title={f.title} detail={f.detail} />
                                ))}
                            </div>

                            {/* Tip */}
                            <div className="rounded-lg border border-zinc-800 bg-zinc-900 px-4 py-3">
                                <p className="text-[11px] leading-relaxed text-zinc-500">
                                    <span className="font-semibold text-zinc-400">Note —</span>{' '}
                                    The solo path skips organization setup entirely. All test management and candidate access flows are available from day one.
                                </p>
                            </div>
                        </div>

                        {/* Right: solo admin card */}
                        <Link
                            href={route('onboarding.solo-admin.create')}
                            className="flex flex-col rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 transition-opacity hover:opacity-90"
                        >
                            <div className="mb-2.5 flex items-start justify-between gap-2">
                                <span className="text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                                    Standalone Access
                                </span>
                                <span className="whitespace-nowrap rounded-full border border-zinc-700 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-zinc-500">
                                    Independent
                                </span>
                            </div>

                            <h3 className="mb-2 text-[17px] font-bold text-white">Solo admin</h3>

                            <p className="mb-4 text-xs leading-relaxed text-zinc-500">
                                Open a direct admin account for building and reviewing tests independently, without organization setup.
                            </p>

                            <ul className="mb-4 space-y-2.5">
                                {[
                                    'No organization record required',
                                    'Straight into solo test creation',
                                    'Simple path for independent assessment operations',
                                ].map((item) => (
                                    <li key={item} className="flex items-start gap-2.5 text-xs text-zinc-300">
                                        <span className="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border border-zinc-700 bg-zinc-800 text-[9px] text-zinc-500">
                                            ✓
                                        </span>
                                        {item}
                                    </li>
                                ))}
                            </ul>

                            <p className="mb-5 flex-1 text-[10px] text-zinc-600">
                                Best when one admin owns the full test workflow.
                            </p>

                            <span className="mt-auto block rounded-xl border border-zinc-700 py-3 text-center text-xs font-bold text-zinc-400">
                                Continue as solo admin
                            </span>
                        </Link>
                    </div>

                    {/* ── Login row ───────────────────────────────────────── */}
                    <div className="mt-8 flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 px-5 py-4">
                        <span className="text-xs text-zinc-600">Already have an account?</span>
                        <Link
                            href={route('login')}
                            className="text-xs font-semibold text-zinc-300 underline underline-offset-4"
                        >
                            Log in
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