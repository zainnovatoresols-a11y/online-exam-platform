import AccessPathCard from '@/Components/public/AccessPathCard';
import PublicEntryLayout from '@/Layouts/PublicEntryLayout';
import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';

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

export default function Welcome({ auth }: PageProps) {
    return (
        <>
            <Head title="Online Exam Platform" />

            <PublicEntryLayout
                authUser={auth.user}
                eyebrow="Assessment Operations"
                title="Run secure online assessments from one professional workspace."
                description="This platform is designed for organizations and independent admins who need structured test delivery, candidate access control, coding evaluation, proctoring review, and result exports in one place."
                rightSectionClassName="space-y-4"
                supportingContent={
                    <div className="space-y-6">
                        <div className="grid gap-4 sm:grid-cols-3">
                            {metrics.map((metric) => (
                                <div
                                    key={metric.label}
                                    className="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm"
                                >
                                    <p className="text-sm font-semibold text-zinc-500">
                                        {metric.label}
                                    </p>
                                    <p className="mt-3 text-2xl font-semibold text-zinc-950">
                                        {metric.value}
                                    </p>
                                    <p className="mt-2 text-sm leading-6 text-zinc-600">
                                        {metric.description}
                                    </p>
                                </div>
                            ))}
                        </div>

                        <div className="rounded-lg border border-zinc-800 bg-zinc-950 p-6 text-zinc-100 shadow-sm">
                            <div className="flex flex-wrap items-start justify-between gap-4 border-b border-zinc-800 pb-5">
                                <div>
                                    <p className="text-sm font-semibold text-zinc-400">
                                        Platform Overview
                                    </p>
                                    <h2 className="mt-2 text-xl font-semibold">
                                        From onboarding to reviewed result
                                    </h2>
                                </div>
                                <span className="rounded-full border border-emerald-400/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-200">
                                    Admin-first access
                                </span>
                            </div>

                            <div className="mt-5 space-y-5">
                                {previewRows.map((row, index) => (
                                    <div
                                        key={row.title}
                                        className={`flex gap-4 ${
                                            index > 0
                                                ? 'border-t border-zinc-800 pt-5'
                                                : ''
                                        }`}
                                    >
                                        <div className="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-zinc-700 text-sm font-semibold text-zinc-300">
                                            {index + 1}
                                        </div>
                                        <div>
                                            <h3 className="text-base font-semibold text-white">
                                                {row.title}
                                            </h3>
                                            <p className="mt-2 text-sm leading-6 text-zinc-300">
                                                {row.detail}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                }
                afterContent={
                    <div className="space-y-10">
                        <section className="space-y-5">
                            <div className="max-w-3xl">
                                <p className="text-sm font-semibold text-zinc-500">
                                    What the project covers
                                </p>
                                <h2 className="mt-2 text-2xl font-semibold text-zinc-950">
                                    Built for technical test delivery, review,
                                    and evidence-based decision making
                                </h2>
                            </div>

                            <div className="grid gap-4 lg:grid-cols-4">
                                {platformCapabilities.map((capability) => (
                                    <div
                                        key={capability.title}
                                        className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm"
                                    >
                                        <h3 className="text-base font-semibold text-zinc-950">
                                            {capability.title}
                                        </h3>
                                        <p className="mt-3 text-sm leading-6 text-zinc-600">
                                            {capability.description}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </section>

                        <section className="grid gap-6 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
                            <div className="max-w-xl">
                                <p className="text-sm font-semibold text-zinc-500">
                                    Getting started
                                </p>
                                <h2 className="mt-2 text-2xl font-semibold text-zinc-950">
                                    Choose the admin path that matches your
                                    operating model, then build from there
                                </h2>
                                <p className="mt-3 text-sm leading-6 text-zinc-600">
                                    Candidate accounts are created through test
                                    invitations and public test registration
                                    after an admin publishes a test. The first
                                    decision here is how your admin access
                                    should be structured.
                                </p>
                            </div>

                            <div className="space-y-4">
                                {workflowStages.map((stage, index) => (
                                    <div
                                        key={stage.label}
                                        className="flex gap-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm"
                                    >
                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-sm font-semibold text-white">
                                            {index + 1}
                                        </div>
                                        <div>
                                            <h3 className="text-base font-semibold text-zinc-950">
                                                {stage.label}
                                            </h3>
                                            <p className="mt-2 text-sm leading-6 text-zinc-600">
                                                {stage.description}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>
                    </div>
                }
            >
                <div className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                    <p className="text-sm font-semibold text-zinc-950">
                        Choose your admin access
                    </p>
                    <p className="mt-2 text-sm leading-6 text-zinc-600">
                        Start with the setup that matches your team structure.
                        Candidate access is created later from test invites and
                        public assessment flows.
                    </p>
                </div>

                <AccessPathCard
                    eyebrow="For Teams"
                    title="Organization owner"
                    description="Create the parent workspace for your organization, then add admins who manage tests inside that organization."
                    href={route('onboarding.organization-owner.create')}
                    cta="Start organization setup"
                    helper="Best for institutions, hiring teams, and shared assessment operations."
                    highlights={[
                        'Creates the organization and owner account together',
                        'Supports adding scoped admins after setup',
                        'Keeps organization-level ownership in one place',
                    ]}
                    variant="primary"
                />

                <AccessPathCard
                    eyebrow="For Independent Use"
                    title="Solo admin"
                    description="Create a standalone admin account for building and reviewing tests without creating an organization record."
                    href={route('onboarding.solo-admin.create')}
                    cta="Start solo setup"
                    helper="Best for recruiters, trainers, and independent evaluation workflows."
                    highlights={[
                        'No organization setup required',
                        'Direct access to solo test creation and review',
                        'Simple path for one-admin operations',
                    ]}
                />

                {!auth.user && (
                    <div className="flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-5 py-4 text-sm text-zinc-600 shadow-sm">
                        <span>Already have an account?</span>
                        <Link
                            href={route('login')}
                            className="font-semibold text-zinc-950 underline underline-offset-4"
                        >
                            Log in
                        </Link>
                    </div>
                )}
            </PublicEntryLayout>
        </>
    );
}
