import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type Test = {
    id: number;
    title: string;
    duration_minutes: number;
    pass_mark: number;
    status: string;
    questions_count: number;
};

type Props = {
    tests: {
        data: Test[];
        from: number | null;
    };
};

const statusTone: Record<string, string> = {
    draft: 'border-amber-400/20 bg-amber-400/10 text-amber-200',
    published: 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200',
    closed: 'border-zinc-500/20 bg-zinc-500/10 text-zinc-300',
};

function formatStatus(status: string) {
    return status
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export default function Index({ tests }: Props) {
    return (
        <AuthenticatedLayout
            theme="dark"
            header={
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                        Assessment Library
                    </p>
                    <h2 className="mt-2 text-xl font-semibold leading-tight text-white">
                        Tests
                    </h2>
                </div>
            }
        >
            <Head title="Tests" />

            <div className="bg-zinc-950 px-4 py-10 text-zinc-100 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">
                                Manage assessments
                            </p>
                            <h1 className="mt-2 text-2xl font-bold text-white">
                                All tests
                            </h1>
                            <p className="mt-2 max-w-2xl text-sm leading-relaxed text-zinc-500">
                                Review test status, question coverage, duration,
                                and continue setup before publishing.
                            </p>
                        </div>

                        <Link
                            href={route('admin.tests.create')}
                            className="inline-flex h-11 items-center justify-center rounded-xl bg-emerald-500 px-5 text-sm font-bold text-black transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                        >
                            Create test
                        </Link>
                    </div>

                    <div className="overflow-hidden rounded-[18px] border border-zinc-800 bg-zinc-900 shadow-2xl shadow-black/20">
                        <div className="border-b border-zinc-800 px-6 py-5">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 className="text-sm font-semibold text-white">
                                        Test registry
                                    </h3>
                                    <p className="mt-1 text-xs text-zinc-500">
                                        {tests.data.length} test
                                        {tests.data.length === 1 ? '' : 's'} on
                                        this page
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-zinc-800">
                                <thead className="bg-zinc-950/70">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                            #
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                            Title
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                            Duration
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                            Questions
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-zinc-800 bg-zinc-900">
                                    {tests.data.map((test, index) => (
                                        <tr
                                            key={test.id}
                                            className="transition hover:bg-zinc-800/50"
                                        >
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-zinc-500">
                                                {(tests.from ?? 1) + index}
                                            </td>
                                            <td className="min-w-64 px-6 py-4 text-sm font-semibold text-white">
                                                {test.title}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                <span
                                                    className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ${
                                                        statusTone[
                                                            test.status.toLowerCase()
                                                        ] ??
                                                        'border-zinc-600 bg-zinc-950 text-zinc-300'
                                                    }`}
                                                >
                                                    {formatStatus(test.status)}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-zinc-400">
                                                {test.duration_minutes} minutes
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-zinc-400">
                                                {test.questions_count}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                                                <div className="inline-flex items-center gap-4">
                                                    <Link
                                                        href={route(
                                                            'admin.tests.show',
                                                            test.id,
                                                        )}
                                                        className="font-semibold text-emerald-300 underline-offset-4 transition hover:text-emerald-200 hover:underline"
                                                    >
                                                        View
                                                    </Link>
                                                    {test.status !==
                                                        'published' && (
                                                        <Link
                                                            href={route(
                                                                'admin.tests.edit',
                                                                test.id,
                                                            )}
                                                            className="font-semibold text-zinc-300 underline-offset-4 transition hover:text-white hover:underline"
                                                        >
                                                            Edit
                                                        </Link>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {tests.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={6}
                                                className="px-6 py-12 text-center text-sm text-zinc-500"
                                            >
                                                No tests created yet.
                                                <Link
                                                    href={route(
                                                        'admin.tests.create',
                                                    )}
                                                    className="ml-2 font-semibold text-emerald-300 underline-offset-4 hover:text-emerald-200 hover:underline"
                                                >
                                                    Create your first test
                                                </Link>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
