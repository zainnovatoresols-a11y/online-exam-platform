import { PageProps } from '@/types';
import QuizPlatformLogo from '@/Components/QuizPlatformLogo';
import { Head, Link } from '@inertiajs/react';

// ─── Data ────────────────────────────────────────────────────────────────────

const platformCapabilities = [
    {
        title: 'Assessment building',
        description:
            'Create organization or solo tests with controlled draft, publish, and close workflows.',
    },
    {
        title: 'Candidate delivery',
        description:
            'Use invite flows and public test links to onboard candidates only after an assessment is ready.',
    },
    {
        title: 'Evidence review',
        description:
            'Inspect proctoring events, screen and camera recordings, and coding results in one review flow.',
    },
    {
        title: 'Operational exports',
        description:
            'Download CSV and PDF reports for audit trails, internal reviews, and client handoff.',
    },
];

const workflowStages = [
    {
        label: 'Set up access',
        description:
            'Start as an organization owner if you need team management, or as a solo admin for independent operation.',
    },
    {
        label: 'Launch assessments',
        description:
            'Create tests, add MCQ and coding questions, publish, and send controlled candidate access.',
    },
    {
        label: 'Review outcomes',
        description:
            'Check scores, proctoring evidence, exports, and review decisions before finalizing results.',
    },
];

const previewRows = [
    {
        title: 'Organization and solo ownership',
        detail:
            'Separate account paths keep multi-admin teams and standalone admins in the right operational model.',
    },
    {
        title: 'Test operations in one flow',
        detail:
            'Build MCQ and coding assessments, publish safely, and manage candidate access without leaving the platform.',
    },
    {
        title: 'Result and proctoring review',
        detail:
            'Use evidence-first review screens for coding runs, proctoring alerts, risk scoring, recordings, and exports.',
    },
];

const metrics = [
    {
        label: 'Admin paths',
        value: '2',
        description: 'Organization owner and solo admin onboarding',
    },
    {
        label: 'Assessment types',
        value: 'MCQ + Coding',
        description: 'Built for mixed technical evaluation workflows',
    },
    {
        label: 'Review coverage',
        value: 'Results + Proctoring',
        description: 'Operational screens for decision-ready review',
    },
];

// Live sessions preview is intentionally hidden until live monitoring is implemented.
// const liveSessions = [
//     { initials: 'AR', name: 'Ahmed Raza', progress: '4/10', time: '23:45', warn: true, badge: 'Warning 2' },
//     { initials: 'SK', name: 'Sara Khan', progress: '7/10', time: '18:22', warn: false, badge: 'Clean' },
//     { initials: 'UA', name: 'Usman Ali', progress: '2/10', time: '31:08', warn: true, badge: 'Warning 1' },
// ];

// ─── Component ───────────────────────────────────────────────────────────────

export default function Welcome({ auth }: PageProps) {
    return (
        <>
            <Head title="Online Quiz Platform" />

            <div className="min-h-screen bg-zinc-950 text-zinc-100" style={{ fontFamily: "-apple-system, BlinkMacSystemFont, 'Inter', sans-serif" }}>

                {/* ── Sticky nav ──────────────────────────────────────────── */}
                <nav
                    className="sticky top-0 z-50 flex h-14 items-center justify-between border-b border-white/5 px-6 backdrop-blur-md"
                    style={{ background: 'rgba(9,9,11,0.85)' }}
                >
                    <div className="flex items-center gap-2.5">
                        <QuizPlatformLogo markClassName="h-7 w-7 rounded-lg" />
                    </div>

                    <div className="flex items-center gap-3">
                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="rounded-lg bg-emerald-500 px-3.5 py-1.5 text-xs font-bold text-black hover:bg-emerald-400 transition-colors"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="text-xs text-zinc-500 hover:text-zinc-300 transition-colors"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={route('onboarding.organization-owner.create')}
                                    className="rounded-lg bg-emerald-500 px-3.5 py-1.5 text-xs font-bold text-black hover:bg-emerald-400 transition-colors"
                                >
                                    Get started
                                </Link>
                            </>
                        )}
                    </div>
                </nav>

                {/* ── Hero ────────────────────────────────────────────────── */}
                <section className="relative overflow-hidden px-6 pb-16 pt-20 text-center">
                    {/* Background glow */}
                    <div
                        className="pointer-events-none absolute left-1/2 top-[-40px] h-[400px] w-[600px] -translate-x-1/2"
                        style={{ background: 'radial-gradient(circle, rgba(16,185,129,0.12) 0%, transparent 70%)' }}
                    />

                    <div className="relative">
                        {/* Animated eyebrow pill */}
                        <div
                            className="mb-6 inline-flex items-center gap-2 rounded-full border px-3.5 py-1"
                            style={{ borderColor: 'rgba(16,185,129,0.2)', background: 'rgba(16,185,129,0.06)' }}
                        >
                            <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400" />
                            <span className="text-[10px] font-semibold uppercase tracking-widest text-emerald-400">
                                Assessment Operations Platform
                            </span>
                        </div>

                        {/* Heading */}
                        <h1 className="mb-4 text-4xl font-extrabold leading-[1.1] tracking-tight text-white sm:text-5xl">
                            Secure online assessments.<br />
                            <span className="text-emerald-400">Evidence-backed</span> results.
                        </h1>

                        <p className="mx-auto mb-7 max-w-[480px] text-sm leading-relaxed text-zinc-500">
                            Structured test delivery, proctoring review, coding evaluation, and result exports — in one professional workspace.
                        </p>

                        {/* CTA row */}
                        <div className="mb-12 flex flex-wrap justify-center gap-2.5">
                            <Link
                                href={route('onboarding.organization-owner.create')}
                                className="rounded-xl bg-emerald-500 px-7 py-3 text-sm font-bold text-black hover:bg-emerald-400 transition-colors"
                            >
                                Start for free
                            </Link>
                            <Link
                                href={route('login')}
                                className="rounded-xl border border-zinc-700 px-7 py-3 text-sm font-semibold text-zinc-400 hover:border-zinc-600 hover:text-zinc-300 transition-colors"
                            >
                                Log in to your account →
                            </Link>
                        </div>

                        {/*
                            Live sessions preview is hidden for now because live monitoring
                            has not been implemented yet.
                        */}
                    </div>
                </section>

                {/* ── Metrics strip ───────────────────────────────────────── */}
                <div
                    className="grid grid-cols-3"
                    style={{
                        background: 'rgba(24,24,27,0.3)',
                        borderTop: '1px solid rgba(39,39,42,0.5)',
                        borderBottom: '1px solid rgba(39,39,42,0.5)',
                    }}
                >
                    {metrics.map((m, i) => (
                        <div
                            key={m.label}
                            className="px-4 py-6 text-center"
                            style={i < metrics.length - 1 ? { borderRight: '1px solid rgba(39,39,42,0.5)' } : {}}
                        >
                            <p className="text-[22px] font-bold text-white">{m.value}</p>
                            <p className="mt-0.5 text-[11px] font-semibold text-zinc-300">{m.label}</p>
                            <p className="mt-0.5 text-[10px] text-zinc-600">{m.description}</p>
                        </div>
                    ))}
                </div>

                {/* ── Platform overview card ──────────────────────────────── */}
                <div className="mx-auto max-w-4xl px-6 py-12">
                    <div className="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900">
                        {/* Card header */}
                        <div className="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-800 px-7 py-6">
                            <div>
                                <p className="mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                                    Platform Overview
                                </p>
                                <h2 className="text-lg font-bold text-white">From onboarding to reviewed result</h2>
                            </div>
                            <span
                                className="rounded-full border px-2.5 py-1 text-[10px] font-semibold text-emerald-400 whitespace-nowrap"
                                style={{ borderColor: 'rgba(16,185,129,0.2)', background: 'rgba(16,185,129,0.06)' }}
                            >
                                Admin-first access
                            </span>
                        </div>

                        {/* Numbered rows */}
                        {previewRows.map((row, i) => (
                            <div
                                key={row.title}
                                className="flex gap-4 px-7 py-5"
                                style={i > 0 ? { borderTop: '1px solid rgba(39,39,42,0.5)' } : {}}
                            >
                                <div className="flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-full border border-zinc-700 text-xs font-bold text-emerald-400">
                                    {i + 1}
                                </div>
                                <div>
                                    <h3 className="mb-1.5 text-sm font-semibold text-white">{row.title}</h3>
                                    <p className="text-xs leading-relaxed text-zinc-500">{row.detail}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* ── Capabilities grid ───────────────────────────────────── */}
                <div
                    className="py-12"
                    style={{
                        background: 'rgba(24,24,27,0.2)',
                        borderTop: '1px solid rgba(39,39,42,0.5)',
                    }}
                >
                    <div className="mx-auto max-w-4xl px-6">
                        <p className="mb-2.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                            What the project covers
                        </p>
                        <h2 className="mb-8 text-2xl font-bold tracking-tight text-white">
                            Built for technical test delivery and evidence-based decisions
                        </h2>

                        <div className="grid grid-cols-2 gap-2.5">
                            {platformCapabilities.map((cap) => (
                                <div key={cap.title} className="rounded-xl border border-zinc-800 bg-zinc-900 p-5">
                                    {/* Green dot icon */}
                                    <div
                                        className="mb-3.5 flex h-8 w-8 items-center justify-center rounded-lg border"
                                        style={{
                                            borderColor: 'rgba(16,185,129,0.2)',
                                            background: 'rgba(16,185,129,0.06)',
                                        }}
                                    >
                                        <span className="h-2 w-2 rounded-full bg-emerald-400" />
                                    </div>
                                    <h3 className="mb-1.5 text-xs font-semibold text-white">{cap.title}</h3>
                                    <p className="text-[11px] leading-relaxed text-zinc-500">{cap.description}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* ── Getting started / workflow steps ────────────────────── */}
                <div className="mx-auto max-w-4xl px-6 py-12">
                    <div className="grid gap-7 lg:grid-cols-2 lg:items-start">
                        {/* Left: copy */}
                        <div>
                            <p className="mb-2.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                                Getting started
                            </p>
                            <h2 className="mb-2.5 text-2xl font-bold leading-tight tracking-tight text-white">
                                Choose the admin path that matches your operating model, then build from there
                            </h2>
                            <p className="text-xs leading-relaxed text-zinc-500">
                                Candidate accounts are created through test invitations and public test registration after an admin publishes a test. The first decision here is how your admin access should be structured.
                            </p>
                        </div>

                        {/* Right: step cards */}
                        <div className="space-y-2">
                            {workflowStages.map((stage, i) => (
                                <div
                                    key={stage.label}
                                    className="flex gap-3.5 rounded-xl border border-zinc-800 bg-zinc-900 px-5 py-4"
                                >
                                    <span className="shrink-0 pt-0.5 text-2xl font-black leading-none text-zinc-800">
                                        {String(i + 1).padStart(2, '0')}
                                    </span>
                                    <div>
                                        <h3 className="mb-1 text-xs font-semibold text-white">{stage.label}</h3>
                                        <p className="text-[11px] leading-relaxed text-zinc-500">{stage.description}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* ── Access path cards ───────────────────────────────────── */}
                <div
                    className="px-6 py-16"
                    style={{
                        background: 'rgba(24,24,27,0.3)',
                        borderTop: '1px solid rgba(39,39,42,0.5)',
                    }}
                >
                    <div className="mx-auto max-w-3xl">
                        {/* Section header */}
                        <div className="mb-9 text-center">
                            <p className="mb-2.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                                Access setup
                            </p>
                            <h2 className="mb-1.5 text-2xl font-bold tracking-tight text-white">
                                Choose your admin access
                            </h2>
                            <p className="mx-auto max-w-xs text-xs text-zinc-500">
                                Start with the setup that matches your team structure. Candidate access is created later from test invites and public assessment flows.
                            </p>
                        </div>

                        <div className="grid gap-3.5 sm:grid-cols-2">
                            {/* ── Organization owner — primary (emerald) ── */}
                            <Link
                                href={route('onboarding.organization-owner.create')}
                                className="flex flex-col rounded-[18px] bg-zinc-900 p-7 transition-opacity hover:opacity-90"
                                style={{
                                    border: '1px solid rgba(16,185,129,0.3)',
                                    boxShadow: '0 0 60px -20px rgba(16,185,129,0.15)',
                                }}
                            >
                                <div className="mb-2.5 flex items-start justify-between gap-2">
                                    <span className="text-[10px] font-semibold uppercase tracking-widest text-emerald-400">
                                        Organization owner
                                    </span>
                                    <span className="rounded-full bg-emerald-500 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-black whitespace-nowrap">
                                        For Teams
                                    </span>
                                </div>
                                <h3 className="mb-2 text-[17px] font-bold text-white">
                                    Team-based assessment operations
                                </h3>
                                <p className="mb-4 text-xs leading-relaxed text-zinc-500">
                                    Create the parent workspace, then add scoped admins who manage tests inside your organization.
                                </p>
                                <ul className="mb-4 space-y-2.5">
                                    {[
                                        'Creates the organization and owner account together',
                                        'Supports adding scoped admins after setup',
                                        'Keeps organization-level ownership in one place',
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
                                    Best for institutions, hiring teams, and shared assessment operations.
                                </p>
                                <span className="mt-auto block rounded-xl bg-emerald-500 py-3 text-center text-xs font-bold text-black">
                                    Start organization setup
                                </span>
                            </Link>

                            {/* ── Solo admin — secondary (zinc) ── */}
                            <Link
                                href={route('onboarding.solo-admin.create')}
                                className="flex flex-col rounded-[18px] border border-zinc-800 bg-zinc-900 p-7 transition-opacity hover:opacity-90"
                            >
                                <div className="mb-2.5 flex items-start justify-between gap-2">
                                    <span className="text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                                        Solo admin
                                    </span>
                                    <span className="rounded-full border border-zinc-700 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-zinc-500 whitespace-nowrap">
                                        Independent use
                                    </span>
                                </div>
                                <h3 className="mb-2 text-[17px] font-bold text-white">
                                    Standalone admin account
                                </h3>
                                <p className="mb-4 text-xs leading-relaxed text-zinc-500">
                                    Create a standalone admin account for building and reviewing tests without creating an organization record.
                                </p>
                                <ul className="mb-4 space-y-2.5">
                                    {[
                                        'No organization setup required',
                                        'Direct access to solo test creation and review',
                                        'Simple path for one-admin operations',
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
                                    Best for recruiters, trainers, and independent evaluation workflows.
                                </p>
                                <span className="mt-auto block rounded-xl border border-zinc-700 py-3 text-center text-xs font-bold text-zinc-400">
                                    Start solo setup
                                </span>
                            </Link>
                        </div>

                        {/* Login link — only when not authenticated */}
                        {!auth.user && (
                            <p className="mt-6 text-center text-xs text-zinc-600">
                                Already have an account?{' '}
                                <Link
                                    href={route('login')}
                                    className="text-zinc-300 underline underline-offset-[3px]"
                                >
                                    Log in
                                </Link>
                            </p>
                        )}
                    </div>
                </div>

                {/* ── Footer ──────────────────────────────────────────────── */}
                <footer
                    className="flex items-center justify-between px-6 py-6"
                    style={{ borderTop: '1px solid rgba(39,39,42,0.5)' }}
                >
                    <div className="flex items-center gap-2.5">
                        <QuizPlatformLogo markClassName="h-[26px] w-[26px] rounded-lg" />
                    </div>
                    <span className="text-[10px] text-zinc-700">
                        (c) 2025 Online Quiz Platform. All rights reserved.
                    </span>
                </footer>

            </div>
        </>
    );
}
