import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type ManagedOrganization = {
    id: number;
    name: string;
    admins_count: number;
    tests_count: number;
};

type Props = {
    managedOrganization: ManagedOrganization | null;
    canCreateOrganizations: boolean;
};

export default function Dashboard({
    managedOrganization,
    canCreateOrganizations,
}: Props) {
    const isOrganizationOwner = managedOrganization !== null;

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    {isOrganizationOwner
                        ? 'Organization Owner Dashboard'
                        : 'Super Admin Dashboard'}
                </h2>
            }
        >
            <Head
                title={
                    isOrganizationOwner
                        ? 'Organization Owner Dashboard'
                        : 'Super Admin Dashboard'
                }
            />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h1 className="text-2xl font-semibold text-gray-900">
                            {isOrganizationOwner
                                ? managedOrganization.name
                                : 'Super Admin Dashboard'}
                        </h1>
                        <p className="mt-2 text-sm text-gray-600">
                            {isOrganizationOwner
                                ? 'Manage your organization and add admin accounts from here.'
                                : 'Manage organizations and their admin users from here.'}
                        </p>
                    </div>

                    <div className="grid gap-6 md:grid-cols-2">
                        {managedOrganization ? (
                            <>
                                <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                                    <h3 className="text-lg font-semibold text-gray-900">
                                        Organization Overview
                                    </h3>
                                    <dl className="mt-4 space-y-3 text-sm text-gray-600">
                                        <div className="flex items-center justify-between gap-4">
                                            <dt>Organization</dt>
                                            <dd className="font-medium text-gray-900">
                                                {managedOrganization.name}
                                            </dd>
                                        </div>
                                        <div className="flex items-center justify-between gap-4">
                                            <dt>Admins</dt>
                                            <dd className="font-medium text-gray-900">
                                                {managedOrganization.admins_count}
                                            </dd>
                                        </div>
                                        <div className="flex items-center justify-between gap-4">
                                            <dt>Tests</dt>
                                            <dd className="font-medium text-gray-900">
                                                {managedOrganization.tests_count}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                                    <h3 className="text-lg font-semibold text-gray-900">
                                        Organization Access
                                    </h3>
                                    <p className="mt-2 text-sm text-gray-600">
                                        Open your organization profile, update details,
                                        and create admin accounts for your team.
                                    </p>
                                    <div className="mt-6 flex flex-wrap gap-3">
                                        <Link
                                            href={route(
                                                'super-admin.organizations.show',
                                                managedOrganization.id,
                                            )}
                                            className="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white"
                                        >
                                            View organization
                                        </Link>
                                        <Link
                                            href={route(
                                                'super-admin.organizations.admins.create',
                                                managedOrganization.id,
                                            )}
                                            className="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700"
                                        >
                                            Add admin
                                        </Link>
                                    </div>
                                </div>
                            </>
                        ) : (
                            <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                                <h3 className="text-lg font-semibold text-gray-900">
                                    Organizations
                                </h3>
                                <p className="mt-2 text-sm text-gray-600">
                                    Create and manage organizations for exam admins.
                                </p>
                                <div className="mt-6 flex flex-wrap gap-3">
                                    {canCreateOrganizations && (
                                        <Link
                                            href={route(
                                                'super-admin.organizations.create',
                                            )}
                                            className="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white"
                                        >
                                            Create organization
                                        </Link>
                                    )}
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
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
