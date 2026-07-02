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
    created_at?: string;
};

type OrganizationTest = {
    id: number;
    title: string;
    status: string;
    creator: {
        id: number;
        name: string;
        email: string;
    } | null;
    questions_count: number;
    attempts_count: number;
    invitations_count: number;
    published_at: string | null;
    closed_at: string | null;
    created_at: string | null;
    results_url: string;
    analytics_url: string;
};

type Props = {
    organization: Organization;
    admins: Admin[];
    tests: OrganizationTest[];
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
const tableCellClass = 'whitespace-nowrap px-6 py-4 text-sm text-zinc-400';
const statusBadgeClass =
    'inline-flex rounded-full border border-zinc-700 bg-zinc-950 px-3 py-1 text-xs font-semibold capitalize text-zinc-300';

export default function Show({ organization, admins, tests }: Props) {
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
                                    Review organization admins and the tests created
                                    inside this workspace.
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

                        <div className="mt-6 grid gap-3 sm:grid-cols-3">
                            <div className="rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3">
                                <p className="text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                                    Admins
                                </p>
                                <p className="mt-2 text-2xl font-bold text-white">
                                    {admins.length}
                                </p>
                            </div>
                            <div className="rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3">
                                <p className="text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                                    Tests
                                </p>
                                <p className="mt-2 text-2xl font-bold text-white">
                                    {organization.tests_count}
                                </p>
                            </div>
                            <div className="rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3">
                                <p className="text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                                    Attempts
                                </p>
                                <p className="mt-2 text-2xl font-bold text-white">
                                    {tests.reduce(
                                        (total, test) =>
                                            total + test.attempts_count,
                                        0,
                                    )}
                                </p>
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
                                            <td className={tableCellClass}>
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

                    <div className={tableWrapperClass}>
                        <div className="border-b border-zinc-800 px-6 py-4">
                            <h3 className="text-lg font-semibold text-white">
                                Organization Tests
                            </h3>
                            <p className="mt-1 text-sm text-zinc-500">
                                Tests created by admins in this organization, with
                                direct access to results and analytics.
                            </p>
                        </div>
                        <div className="min-w-[980px]">
                            <table className="min-w-full divide-y divide-zinc-800">
                                <thead className="bg-zinc-950/80">
                                    <tr>
                                        <th className={tableHeadingClass}>
                                            Test
                                        </th>
                                        <th className={tableHeadingClass}>
                                            Created by
                                        </th>
                                        <th className={tableHeadingClass}>
                                            Status
                                        </th>
                                        <th className={tableHeadingClass}>
                                            Invitations
                                        </th>
                                        <th className={tableHeadingClass}>
                                            Questions
                                        </th>
                                        <th className={tableHeadingClass}>
                                            Attempts
                                        </th>
                                        <th className="px-6 py-3" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-zinc-800 bg-zinc-900">
                                    {tests.map((test) => (
                                        <tr key={test.id}>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                <div className="font-semibold text-white">
                                                    {test.title}
                                                </div>
                                                <div className="mt-1 text-xs text-zinc-500">
                                                    Created {formatDate(test.created_at)}
                                                </div>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                <div className="font-semibold text-zinc-200">
                                                    {test.creator?.name ?? 'Unknown admin'}
                                                </div>
                                                <div className="mt-1 text-xs text-zinc-500">
                                                    {test.creator?.email ?? '-'}
                                                </div>
                                            </td>
                                            <td className={tableCellClass}>
                                                <span className={statusBadgeClass}>
                                                    {test.status}
                                                </span>
                                            </td>
                                            <td className={tableCellClass}>
                                                {test.invitations_count}
                                            </td>
                                            <td className={tableCellClass}>
                                                {test.questions_count}
                                            </td>
                                            <td className={tableCellClass}>
                                                {test.attempts_count}
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm">
                                                <div className="flex justify-end gap-3">
                                                    <Link
                                                        href={test.results_url}
                                                        className={secondaryLinkClass}
                                                    >
                                                        Results
                                                    </Link>
                                                    <Link
                                                        href={test.analytics_url}
                                                        className={secondaryLinkClass}
                                                    >
                                                        Analytics
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {tests.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={7}
                                                className="px-6 py-10 text-center text-sm text-zinc-500"
                                            >
                                                No tests have been created in this
                                                organization yet.
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

function formatDate(value: string | null): string {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
