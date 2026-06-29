import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

type Test = {
    id: number;
    title: string;
    description: string | null;
    duration_minutes: number;
    pass_mark: number;
    starts_at: string | null;
    status: string;
    questions_count: number;
    public_url: string | null;
    public_access_enabled: boolean;
};

const statusTone: Record<string, string> = {
    draft: 'border-amber-400/20 bg-amber-400/10 text-amber-200',
    published: 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200',
    closed: 'border-zinc-500/20 bg-zinc-500/10 text-zinc-300',
};

const actionLinkClass =
    'inline-flex h-10 items-center justify-center rounded-xl border border-zinc-700 px-4 text-sm font-semibold text-zinc-300 transition hover:border-zinc-600 hover:text-white';
const detailPanelClass = 'rounded-2xl border border-zinc-800 bg-zinc-950/70 p-5';
const detailLabelClass = 'text-xs font-semibold uppercase tracking-wider text-zinc-500';
const detailValueClass = 'mt-2 text-sm font-semibold text-white';
const primaryButtonClass =
    'inline-flex h-11 items-center justify-center rounded-xl bg-emerald-500 px-5 text-sm font-bold text-black transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40';
const secondaryButtonClass =
    'inline-flex h-11 items-center justify-center rounded-xl border border-zinc-700 px-5 text-sm font-semibold text-zinc-300 transition hover:border-zinc-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-zinc-600/50';
const dangerButtonClass =
    'inline-flex h-11 items-center justify-center rounded-xl border border-red-500/30 bg-red-500/10 px-5 text-sm font-semibold text-red-200 transition hover:border-red-400/50 hover:bg-red-500/15 focus:outline-none focus:ring-2 focus:ring-red-400/30';

export default function Show({ test }: { test: Test }) {
    const [confirmingDelete, setConfirmingDelete] = useState(false);

    const publish = () => router.post(route('admin.tests.publish', test.id));
    const close = () => router.post(route('admin.tests.close', test.id));
    const destroy = () => {
        setConfirmingDelete(false);
        router.delete(route('admin.tests.destroy', test.id));
    };

    return (
        <AuthenticatedLayout
            theme="dark"
            header={
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                        Test Workspace
                    </p>
                    <h2 className="mt-2 text-xl font-semibold leading-tight text-white">
                        {test.title}
                    </h2>
                </div>
            }
        >
            <Head title={test.title} />

            <div className="bg-zinc-950 px-4 py-10 text-zinc-100 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div className="rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/20 sm:p-8">
                        <div className="flex flex-wrap items-start justify-between gap-6 border-b border-zinc-800 pb-6">
                            <div>
                                <Link
                                    href={route('admin.tests.index')}
                                    className="text-sm font-semibold text-zinc-400 underline-offset-4 transition hover:text-white hover:underline"
                                >
                                    Back to tests
                                </Link>
                                <div className="mt-5">
                                    <span
                                        className={`inline-flex rounded-full border px-3 py-1 text-xs font-semibold ${
                                            statusTone[
                                                test.status.toLowerCase()
                                            ] ??
                                            'border-zinc-600 bg-zinc-950 text-zinc-300'
                                        }`}
                                    >
                                        {formatStatus(test.status)}
                                    </span>
                                </div>
                                <h1 className="mt-3 text-2xl font-bold text-white">
                                    {test.title}
                                </h1>
                                <p className="mt-3 max-w-3xl text-sm leading-relaxed text-zinc-500">
                                    {test.description || 'No description'}
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-3">
                                <Link
                                    href={route(
                                        'admin.tests.results.index',
                                        test.id,
                                    )}
                                    className={actionLinkClass}
                                >
                                    Results
                                </Link>
                                <Link
                                    href={route(
                                        'admin.tests.questions.index',
                                        test.id,
                                    )}
                                    className={actionLinkClass}
                                >
                                    Questions
                                </Link>
                                <Link
                                    href={route(
                                        'admin.tests.invitations.index',
                                        test.id,
                                    )}
                                    className={actionLinkClass}
                                >
                                    Invitations
                                </Link>
                                {test.status !== 'published' && (
                                    <Link
                                        href={route(
                                            'admin.tests.edit',
                                            test.id,
                                        )}
                                        className={actionLinkClass}
                                    >
                                        Edit
                                    </Link>
                                )}
                            </div>
                        </div>

                        <dl className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div className={detailPanelClass}>
                                <dt className={detailLabelClass}>
                                    Duration
                                </dt>
                                <dd className={detailValueClass}>
                                    {test.duration_minutes} minutes
                                </dd>
                            </div>
                            <div className={detailPanelClass}>
                                <dt className={detailLabelClass}>
                                    Pass mark
                                </dt>
                                <dd className={detailValueClass}>
                                    {test.pass_mark}
                                </dd>
                            </div>
                            <div className={detailPanelClass}>
                                <dt className={detailLabelClass}>
                                    Questions
                                </dt>
                                <dd className={detailValueClass}>
                                    {test.questions_count}
                                </dd>
                            </div>
                            {test.public_access_enabled && (
                                <div className={`${detailPanelClass} sm:col-span-2`}>
                                    <dt className={detailLabelClass}>
                                        Public test URL
                                    </dt>
                                    <dd className="mt-2 break-all text-sm font-semibold text-emerald-200">
                                        {test.public_url ?? 'Not generated yet'}
                                    </dd>
                                    <dd className="mt-2 text-xs leading-relaxed text-zinc-500">
                                        Anyone with this URL can register after
                                        accepting the policy.
                                    </dd>
                                </div>
                            )}
                            <div className={detailPanelClass}>
                                <dt className={detailLabelClass}>
                                    Public access
                                </dt>
                                <dd className={detailValueClass}>
                                    {test.public_access_enabled
                                        ? 'Open'
                                        : 'Invite list only'}
                                </dd>
                            </div>
                            <div className={detailPanelClass}>
                                <dt className={detailLabelClass}>
                                    Start time
                                </dt>
                                <dd className={detailValueClass}>
                                    {test.starts_at
                                        ? formatDateTime(test.starts_at)
                                        : 'Available now'}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div className="flex flex-wrap gap-3 rounded-[18px] border border-zinc-800 bg-zinc-900 p-5 shadow-2xl shadow-black/20">
                        {test.status === 'draft' && (
                            <>
                                <button
                                    type="button"
                                    onClick={publish}
                                    className={primaryButtonClass}
                                >
                                    Publish
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setConfirmingDelete(true)}
                                    className={dangerButtonClass}
                                >
                                    Delete draft test
                                </button>
                            </>
                        )}
                        {test.status === 'closed' && (
                            <>
                                <button
                                    type="button"
                                    onClick={publish}
                                    className={primaryButtonClass}
                                >
                                    Republish
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setConfirmingDelete(true)}
                                    className={dangerButtonClass}
                                >
                                    Delete closed test
                                </button>
                            </>
                        )}
                        {test.status === 'published' && (
                            <button
                                type="button"
                                onClick={close}
                                className={secondaryButtonClass}
                            >
                                Close test
                            </button>
                        )}
                    </div>
                </div>
            </div>

            {confirmingDelete && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 py-6"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="delete-test-title"
                >
                    <div className="w-full max-w-md rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/40">
                        <div className="mb-5 flex h-11 w-11 items-center justify-center rounded-full border border-red-500/30 bg-red-500/10 text-lg font-bold text-red-200">
                            !
                        </div>

                        <h3
                            id="delete-test-title"
                            className="text-lg font-bold text-white"
                        >
                            Are you sure you want to delete this test?
                        </h3>
                        <p className="mt-2 text-sm leading-relaxed text-zinc-500">
                            This will permanently delete "{test.title}". This
                            action cannot be undone.
                        </p>

                        <div className="mt-6 flex flex-wrap justify-end gap-3">
                            <button
                                type="button"
                                onClick={() => setConfirmingDelete(false)}
                                className={secondaryButtonClass}
                            >
                                No
                            </button>
                            <button
                                type="button"
                                onClick={destroy}
                                className={dangerButtonClass}
                            >
                                Yes, delete
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}

function formatStatus(status: string): string {
    return status
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
