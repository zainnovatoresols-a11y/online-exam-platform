import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

type Candidate = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    stack_name: string | null;
};

type Props = {
    candidates: {
        data: Candidate[];
        from: number | null;
    };
    stacks: string[];
    filters: {
        stack: string | null;
    };
};

export default function Index({ candidates, stacks, filters }: Props) {
    const changeStack = (stack: string) => {
        router.get(
            route('admin.candidates.index'),
            stack ? { stack } : {},
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Candidates
                </h2>
            }
        >
            <Head title="Candidates" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <Link
                                href={route('admin.dashboard')}
                                className="text-sm font-medium text-gray-600 underline"
                            >
                                Back to dashboard
                            </Link>

                            <select
                                value={filters.stack ?? ''}
                                onChange={(event) =>
                                    changeStack(event.target.value)
                                }
                                className="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All stacks</option>
                                {stacks.map((stack) => (
                                    <option key={stack} value={stack}>
                                        {stack}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <Link
                            href={route('admin.candidates.create')}
                            className="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white"
                        >
                            Add candidate
                        </Link>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        #
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Candidate
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Phone
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Stack
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {candidates.data.map((candidate, index) => (
                                    <tr key={candidate.id}>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {(candidates.from ?? 1) + index}
                                        </td>
                                        <td className="px-6 py-4 text-sm">
                                            <div className="font-medium text-gray-900">
                                                {candidate.name}
                                            </div>
                                            <div className="text-gray-600">
                                                {candidate.email}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {candidate.phone ?? '-'}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {candidate.stack_name ?? '-'}
                                        </td>
                                        <td className="px-6 py-4 text-right text-sm">
                                            <Link
                                                href={route(
                                                    'admin.candidates.edit',
                                                    candidate.id,
                                                )}
                                                className="font-medium text-gray-900 underline"
                                            >
                                                Edit
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                                {candidates.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-6 py-4 text-sm text-gray-600"
                                        >
                                            No candidates found.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
