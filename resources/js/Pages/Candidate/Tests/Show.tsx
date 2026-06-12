import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

type Test = {
    id: number;
    title: string;
    duration_minutes: number;
    pass_mark: number;
    status: string;
    questions_count: number;
    organization: { id: number; name: string } | null;
    creator: { id: number; name: string; email: string } | null;
};

export default function Show({ test }: { test: Test }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Test Landing
                </h2>
            }
        >
            <Head title={test.title} />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <p className="text-sm font-medium uppercase text-gray-500">
                            {test.status}
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold text-gray-900">
                            {test.title}
                        </h1>

                        <dl className="mt-6 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Organization
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {test.organization?.name ?? 'Solo test'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Examiner
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {test.creator?.name ?? 'Exam Admin'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Duration
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {test.duration_minutes} minutes
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Pass percentage
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {test.pass_mark}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    MCQ questions
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {test.questions_count}
                                </dd>
                            </div>
                        </dl>

                        <p className="mt-6 rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                            Test attempt flow will be implemented in the next stage.
                        </p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
