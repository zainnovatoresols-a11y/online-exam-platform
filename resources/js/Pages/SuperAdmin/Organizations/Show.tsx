import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type Organization = {
    id: number;
    name: string;
    tests_count: number;
};

type Admin = {
    id: number;
    name: string;
    email: string;
};

type Props = {
    organization: Organization;
    admins: Admin[];
};

export default function Show({ organization, admins }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    {organization.name}
                </h2>
            }
        >
            <Head title={organization.name} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900">
                                    Organization Details
                                </h3>
                                <p className="mt-2 text-sm text-gray-600">
                                    Tests: {organization.tests_count}
                                </p>
                            </div>
                            <div className="space-x-4">
                                <Link
                                    href={route(
                                        'super-admin.organizations.admins.create',
                                        organization.id,
                                    )}
                                    className="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white"
                                >
                                    Create admin
                                </Link>
                                <Link
                                    href={route(
                                        'super-admin.organizations.edit',
                                        organization.id,
                                    )}
                                    className="text-sm font-medium text-gray-700 underline"
                                >
                                    Edit
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="border-b border-gray-200 px-6 py-4">
                            <h3 className="text-lg font-semibold text-gray-900">
                                Organization Admins
                            </h3>
                        </div>
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Name
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Email
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {admins.map((admin) => (
                                    <tr key={admin.id}>
                                        <td className="px-6 py-4 text-sm font-medium text-gray-900">
                                            {admin.name}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {admin.email}
                                        </td>
                                    </tr>
                                ))}
                                {admins.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={2}
                                            className="px-6 py-4 text-sm text-gray-600"
                                        >
                                            No admins assigned yet.
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
