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
    };
};

export default function Index({ tests }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Tests
                </h2>
            }
        >
            <Head title="Tests" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex justify-end">
                        <Link
                            href={route('admin.tests.create')}
                            className="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white"
                        >
                            Create test
                        </Link>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Title
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Duration
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Questions
                                    </th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {tests.data.map((test) => (
                                    <tr key={test.id}>
                                        <td className="px-6 py-4 text-sm font-medium text-gray-900">
                                            {test.title}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {test.status}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {test.duration_minutes} minutes
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {test.questions_count}
                                        </td>
                                        <td className="space-x-4 px-6 py-4 text-right text-sm">
                                            <Link
                                                href={route(
                                                    'admin.tests.show',
                                                    test.id,
                                                )}
                                                className="font-medium text-gray-900 underline"
                                            >
                                                View
                                            </Link>
                                            {test.status !== 'published' && (
                                                <Link
                                                    href={route(
                                                        'admin.tests.edit',
                                                        test.id,
                                                    )}
                                                    className="font-medium text-gray-900 underline"
                                                >
                                                    Edit
                                                </Link>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {tests.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-6 py-4 text-sm text-gray-600"
                                        >
                                            No tests created yet.
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
