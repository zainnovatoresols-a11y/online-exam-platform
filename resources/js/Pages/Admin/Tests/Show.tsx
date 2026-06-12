import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

type Test = {
    id: number;
    title: string;
    description: string | null;
    duration_minutes: number;
    pass_mark: number;
    starts_at: string | null;
    status: string;
    questions_count: number;
};

export default function Show({ test }: { test: Test }) {
    const publish = () => router.post(route('admin.tests.publish', test.id));
    const close = () => router.post(route('admin.tests.close', test.id));
    const destroy = () => router.delete(route('admin.tests.destroy', test.id));

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    {test.title}
                </h2>
            }
        >
            <Head title={test.title} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <Link
                                    href={route('admin.tests.index')}
                                    className="text-sm font-medium text-gray-600 underline"
                                >
                                    Back to tests
                                </Link>
                                <p className="mt-3 text-sm font-medium uppercase text-gray-500">
                                    {test.status}
                                </p>
                                <h3 className="mt-2 text-lg font-semibold text-gray-900">
                                    {test.title}
                                </h3>
                                <p className="mt-2 max-w-3xl text-sm text-gray-600">
                                    {test.description || 'No description'}
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-3">
                                <Link
                                    href={route(
                                        'admin.tests.questions.index',
                                        test.id,
                                    )}
                                    className="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700"
                                >
                                    Questions
                                </Link>
                                <Link
                                    href={route(
                                        'admin.tests.invitations.index',
                                        test.id,
                                    )}
                                    className="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700"
                                >
                                    Invitations
                                </Link>
                                {test.status !== 'published' && (
                                    <Link
                                        href={route(
                                            'admin.tests.edit',
                                            test.id,
                                        )}
                                        className="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700"
                                    >
                                        Edit
                                    </Link>
                                )}
                            </div>
                        </div>

                        <dl className="mt-6 grid gap-4 sm:grid-cols-3">
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
                                    Pass mark
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {test.pass_mark}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Questions
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {test.questions_count}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Start time
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {test.starts_at
                                        ? formatDateTime(test.starts_at)
                                        : 'Available now'}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        {test.status === 'draft' && (
                            <>
                                <PrimaryButton onClick={publish}>
                                    Publish
                                </PrimaryButton>
                                <DangerButton onClick={destroy}>
                                    Delete draft test
                                </DangerButton>
                            </>
                        )}
                        {test.status === 'closed' && (
                            <>
                                <PrimaryButton onClick={publish}>
                                    Republish
                                </PrimaryButton>
                                <DangerButton onClick={destroy}>
                                    Delete closed test
                                </DangerButton>
                            </>
                        )}
                        {test.status === 'published' && (
                            <SecondaryButton onClick={close}>
                                Close test
                            </SecondaryButton>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
