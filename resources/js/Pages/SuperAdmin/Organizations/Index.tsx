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

const primaryLinkClass =
    'inline-flex h-11 items-center justify-center rounded-xl bg-emerald-500 px-5 text-sm font-bold text-black transition hover:bg-emerald-400';
const secondaryLinkClass =
    'inline-flex h-10 min-w-20 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-950 px-4 text-sm font-bold text-zinc-300 transition hover:border-emerald-500 hover:text-emerald-300';
const tableWrapperClass =
    'dark-horizontal-scrollbar overflow-x-auto rounded-[18px] border border-zinc-800 bg-zinc-900 shadow-2xl shadow-black/20';
const tableHeadingClass =
    'whitespace-nowrap px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500';
const tableCellClass = 'whitespace-nowrap px-6 py-4 text-sm text-zinc-400';

export default function Index({
    organizations,
    can_create_organizations,
}: Props) {
    return (
        <AuthenticatedLayout
            theme="dark"
            header={
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                        Super Admin
                    </p>
                    <h2 className="mt-2 text-xl font-semibold leading-tight text-white">
                        Organizations
                    </h2>
                </div>
            }
        >
            <Head title="Organizations" />

            <div className="bg-zinc-950 px-4 py-10 text-zinc-100 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    {can_create_organizations && (
                        <div className="flex justify-end">
                            <Link
                                href={route('super-admin.organizations.create')}
                                className={primaryLinkClass}
                            >
                                Create organization
                            </Link>
                        </div>
                    )}

                    <div className={tableWrapperClass}>
                        <div className="min-w-[760px]">
                            <table className="min-w-full divide-y divide-zinc-800">
                                <thead className="bg-zinc-950/80">
                                    <tr>
                                        <th className={tableHeadingClass}>
                                            Name
                                        </th>
                                        <th className={tableHeadingClass}>
                                            Admins
                                        </th>
                                        <th className={tableHeadingClass}>
                                            Tests
                                        </th>
                                        <th className="px-6 py-3" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-zinc-800 bg-zinc-900">
                                    {organizations.data.map((organization) => (
                                        <tr key={organization.id}>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm font-semibold text-white">
                                                {organization.name}
                                            </td>
                                            <td className={tableCellClass}>
                                                {organization.admins_count}
                                            </td>
                                            <td className={tableCellClass}>
                                                {organization.tests_count}
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm">
                                                <div className="flex justify-end gap-3">
                                                    <Link
                                                        href={route(
                                                            'super-admin.organizations.show',
                                                            organization.id,
                                                        )}
                                                        className={secondaryLinkClass}
                                                    >
                                                        View
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
                                            </td>
                                        </tr>
                                    ))}
                                    {organizations.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={4}
                                                className="px-6 py-10 text-center text-sm text-zinc-500"
                                            >
                                                No organizations found.
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
