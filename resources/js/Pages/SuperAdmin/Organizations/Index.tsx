import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type Organization = {
    id: number;
    name: string;
    admins_count: number;
    tests_count: number;
};

type Props = {
    organizations: {
        data: Organization[];
    };
    can_create_organizations: boolean;
};

export default function Index({
    organizations,
    can_create_organizations,
}: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Organizations
                </h2>
            }
        >
            <Head title="Organizations" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {can_create_organizations && (
                        <div className="flex justify-end">
                            <Link
                                href={route('super-admin.organizations.create')}
                                className="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white"
                            >
                                Create organization
                            </Link>
                        </div>
                    )}

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Name
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Admins
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Tests
                                    </th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {organizations.data.map((organization) => (
                                    <tr key={organization.id}>
                                        <td className="px-6 py-4 text-sm font-medium text-gray-900">
                                            {organization.name}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {organization.admins_count}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-600">
                                            {organization.tests_count}
                                        </td>
                                        <td className="space-x-4 px-6 py-4 text-right text-sm">
                                            <Link
                                                href={route(
                                                    'super-admin.organizations.show',
                                                    organization.id,
                                                )}
                                                className="font-medium text-gray-900 underline"
                                            >
                                                View
                                            </Link>
                                            <Link
                                                href={route(
                                                    'super-admin.organizations.edit',
                                                    organization.id,
                                                )}
                                                className="font-medium text-gray-900 underline"
                                            >
                                                Edit
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
