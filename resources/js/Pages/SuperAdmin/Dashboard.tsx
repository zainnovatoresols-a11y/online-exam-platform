import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard() {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Super Admin Dashboard
                </h2>
            }
        >
            <Head title="Super Admin Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h1 className="text-2xl font-semibold text-gray-900">
                            Super Admin Dashboard
                        </h1>
                        <p className="mt-2 text-sm text-gray-600">
                            Manage organizations and their admin users from here.
                        </p>
                    </div>

                    <div className="grid gap-6 md:grid-cols-2">
                        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="text-lg font-semibold text-gray-900">
                                Organizations
                            </h3>
                            <p className="mt-2 text-sm text-gray-600">
                                Create and manage organizations for exam admins.
                            </p>
                            <div className="mt-6 flex flex-wrap gap-3">
                                <Link
                                    href={route(
                                        'super-admin.organizations.create',
                                    )}
                                    className="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white"
                                >
                                    Create organization
                                </Link>
                                <Link
                                    href={route(
                                        'super-admin.organizations.index',
                                    )}
                                    className="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700"
                                >
                                    View organizations
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
