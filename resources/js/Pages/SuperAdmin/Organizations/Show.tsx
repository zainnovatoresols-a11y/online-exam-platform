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

const cardClass =
    'rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/20';
const primaryLinkClass =
    'inline-flex h-11 items-center justify-center rounded-xl bg-emerald-500 px-5 text-sm font-bold text-black transition hover:bg-emerald-400';
const secondaryLinkClass =
    'inline-flex h-11 min-w-24 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-950 px-5 text-sm font-bold text-zinc-300 transition hover:border-emerald-500 hover:text-emerald-300';
const tableWrapperClass =
    'dark-horizontal-scrollbar overflow-x-auto rounded-[18px] border border-zinc-800 bg-zinc-900 shadow-2xl shadow-black/20';
const tableHeadingClass =
    'whitespace-nowrap px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500';

export default function Show({ organization, admins }: Props) {
    return (
        <AuthenticatedLayout
            theme="dark"
            header={
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                        Super Admin
                    </p>
                    <h2 className="mt-2 text-xl font-semibold leading-tight text-white">
                        {organization.name}
                    </h2>
                </div>
            }
        >
            <Head title={organization.name} />

            <div className="bg-zinc-950 px-4 py-10 text-zinc-100 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div className={cardClass}>
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                                    Organization
                                </p>
                                <h3 className="mt-2 text-2xl font-bold text-white">
                                    Organization Details
                                </h3>
                                <p className="mt-2 text-sm text-zinc-400">
                                    Tests: {organization.tests_count}
                                </p>
                            </div>
                            <div className="flex flex-wrap justify-end gap-3">
                                <Link
                                    href={route(
                                        'super-admin.organizations.admins.create',
                                        organization.id,
                                    )}
                                    className={primaryLinkClass}
                                >
                                    Create admin
                                </Link>
                                <Link
                                    href={route(
                                        'super-admin.organizations.edit',
                                        organization.id,
                                    )}
                                    className={secondaryLinkClass}
                                >
                                    Edit
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div className={tableWrapperClass}>
                        <div className="border-b border-zinc-800 px-6 py-4">
                            <h3 className="text-lg font-semibold text-white">
                                Organization Admins
                            </h3>
                        </div>
                        <div className="min-w-[640px]">
                            <table className="min-w-full divide-y divide-zinc-800">
                                <thead className="bg-zinc-950/80">
                                    <tr>
                                        <th className={tableHeadingClass}>
                                            Name
                                        </th>
                                        <th className={tableHeadingClass}>
                                            Email
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-zinc-800 bg-zinc-900">
                                    {admins.map((admin) => (
                                        <tr key={admin.id}>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm font-semibold text-white">
                                                {admin.name}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-zinc-400">
                                                {admin.email}
                                            </td>
                                        </tr>
                                    ))}
                                    {admins.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={2}
                                                className="px-6 py-10 text-center text-sm text-zinc-500"
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
            </div>
        </AuthenticatedLayout>
    );
}
