import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

const workflowCards = [
    {
        step: '01',
        title: 'Build assessments',
        description:
            'Create organization or solo tests, define the duration and pass rules, then add MCQ and coding questions.',
    },
    {
        step: '02',
        title: 'Publish and invite',
        description:
            'Publish when ready, then invite candidates from the test page or share the public link when enabled.',
    },
    {
        step: '03',
        title: 'Review evidence',
        description:
            'Review scores, coding runs, proctoring events, screen and camera recordings, risk score, and final decisions.',
    },
];

const capabilityCards = [
    {
        title: 'MCQ and coding tests',
        detail: 'Create mixed assessments with ordered questions and controlled test lifecycle states.',
    },
    {
        title: 'Candidate access',
        detail: 'Use invitation email flows, CSV bulk invites, and public test access when a test allows it.',
    },
    {
        title: 'Proctoring review',
        detail: 'Inspect violations, recordings, risk level, and manually approve, flag, or reject attempts.',
    },
    {
        title: 'Exports and analytics',
        detail: 'Download CSV/PDF results and open per-test analytics from the test result screens.',
    },
];

export default function Dashboard() {
    return (
        <AuthenticatedLayout
            theme="dark"
            header={
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                        Admin Workspace
                    </p>
                    <h2 className="mt-2 text-xl font-semibold leading-tight text-white">
                        Online Exam Platform
                    </h2>
                </div>
            }
        >
            <Head title="Online Exam Platform" />

            <div className="bg-zinc-950 px-4 py-10 text-zinc-100 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl space-y-8">
                    <section className="overflow-hidden rounded-[18px] border border-zinc-800 bg-zinc-900">
                        <div className="grid gap-8 p-6 sm:p-8 lg:grid-cols-[1.2fr_0.8fr] lg:p-10">
                            <div>
                                <div
                                    className="mb-5 inline-flex items-center gap-2 rounded-full border px-3.5 py-1"
                                    style={{
                                        borderColor: 'rgba(16,185,129,0.2)',
                                        background: 'rgba(16,185,129,0.06)',
                                    }}
                                >
                                    <span className="h-1.5 w-1.5 rounded-full bg-emerald-400" />
                                    <span className="text-[10px] font-bold uppercase tracking-widest text-emerald-400">
                                        Assessment Operations
                                    </span>
                                </div>

                                <h1 className="max-w-3xl text-3xl font-extrabold leading-tight tracking-tight text-white sm:text-4xl">
                                    Manage tests, candidates, results, and proctoring evidence from one workspace.
                                </h1>

                                <p className="mt-4 max-w-2xl text-sm leading-relaxed text-zinc-500">
                                    Start by creating a test, add questions, publish when ready, and review every submitted attempt with score, coding, export, and proctoring context.
                                </p>

                                <div className="mt-7 flex flex-wrap gap-3">
                                    <Link
                                        href={route('admin.tests.create')}
                                        className="inline-flex h-11 items-center justify-center rounded-xl bg-emerald-500 px-5 text-sm font-bold text-black transition hover:bg-emerald-400"
                                    >
                                        Create test
                                    </Link>
                                    <Link
                                        href={route('admin.tests.index')}
                                        className="inline-flex h-11 items-center justify-center rounded-xl border border-zinc-700 px-5 text-sm font-semibold text-zinc-300 transition hover:border-zinc-600 hover:text-white"
                                    >
                                        View tests
                                    </Link>
                                </div>
                            </div>

                            <div className="rounded-2xl border border-zinc-800 bg-zinc-950 p-5">
                                <p className="text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                                    Typical flow
                                </p>

                                <div className="mt-5 space-y-4">
                                    {workflowCards.map((card) => (
                                        <div key={card.step} className="flex gap-4">
                                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-zinc-700 text-xs font-bold text-emerald-400">
                                                {card.step}
                                            </div>
                                            <div>
                                                <h3 className="text-sm font-semibold text-white">
                                                    {card.title}
                                                </h3>
                                                <p className="mt-1 text-xs leading-relaxed text-zinc-500">
                                                    {card.description}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        {capabilityCards.map((card) => (
                            <div
                                key={card.title}
                                className="rounded-2xl border border-zinc-800 bg-zinc-900 p-5"
                            >
                                <div
                                    className="mb-4 flex h-9 w-9 items-center justify-center rounded-lg border"
                                    style={{
                                        borderColor: 'rgba(16,185,129,0.2)',
                                        background: 'rgba(16,185,129,0.06)',
                                    }}
                                >
                                    <span className="h-2 w-2 rounded-full bg-emerald-400" />
                                </div>
                                <h3 className="text-sm font-semibold text-white">
                                    {card.title}
                                </h3>
                                <p className="mt-2 text-xs leading-relaxed text-zinc-500">
                                    {card.detail}
                                </p>
                            </div>
                        ))}
                    </section>

                    <section className="grid gap-4 lg:grid-cols-[0.85fr_1.15fr]">
                        <div className="rounded-2xl border border-zinc-800 bg-zinc-900 p-6">
                            <p className="text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                                Next action
                            </p>
                            <h3 className="mt-3 text-lg font-bold text-white">
                                Open your test list to continue work.
                            </h3>
                            <p className="mt-2 text-sm leading-relaxed text-zinc-500">
                                Invitations, result review, analytics, exports, and question management are available from each test workspace.
                            </p>
                            <Link
                                href={route('admin.tests.index')}
                                className="mt-5 inline-flex rounded-xl border border-zinc-700 px-4 py-2 text-sm font-semibold text-zinc-300 transition hover:border-zinc-600 hover:text-white"
                            >
                                Go to tests
                            </Link>
                        </div>

                        <div className="rounded-2xl border border-zinc-800 bg-zinc-900 p-6">
                            <p className="text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                                Review readiness
                            </p>
                            <div className="mt-4 grid gap-3 sm:grid-cols-3">
                                {['Results', 'Proctoring', 'Exports'].map((item) => (
                                    <div
                                        key={item}
                                        className="rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3"
                                    >
                                        <p className="text-sm font-semibold text-white">
                                            {item}
                                        </p>
                                        <p className="mt-1 text-[11px] leading-relaxed text-zinc-500">
                                            Available after candidates submit attempts.
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
