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

const cardClass =
    'rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/20';
const primaryLinkClass =
    'inline-flex h-11 items-center justify-center rounded-xl bg-emerald-500 px-5 text-sm font-bold text-black transition hover:bg-emerald-400';
const secondaryLinkClass =
    'inline-flex h-11 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-950 px-5 text-sm font-bold text-zinc-300 transition hover:border-emerald-500 hover:text-emerald-300';

export default function Dashboard({
    managedOrganization,
    canCreateOrganizations,
}: Props) {
    const isOrganizationOwner = managedOrganization !== null;

    return (
        <AuthenticatedLayout
            theme="dark"
            header={
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                        Super Admin
                    </p>
                    <h2 className="mt-2 text-xl font-semibold leading-tight text-white">
                        {isOrganizationOwner
                            ? 'Organization Owner Dashboard'
                            : 'Super Admin Dashboard'}
                    </h2>
                </div>
            }
        >
            <Head
                title={
                    isOrganizationOwner
                        ? 'Organization Owner Dashboard'
                        : 'Super Admin Dashboard'
                }
            />

            <div className="bg-zinc-950 px-4 py-10 text-zinc-100 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div className={cardClass}>
                        <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                            Workspace
                        </p>
                        <h1 className="mt-2 text-3xl font-bold text-white">
                            {isOrganizationOwner
                                ? managedOrganization.name
                                : 'Super Admin Dashboard'}
                        </h1>
                        <p className="mt-3 max-w-2xl text-sm leading-relaxed text-zinc-400">
                            {isOrganizationOwner
                                ? 'Manage your organization and add admin accounts from here.'
                                : 'Manage organizations and their admin users from here.'}
                        </p>
                    </div>

                    <div className="grid gap-6 md:grid-cols-2">
                        {managedOrganization ? (
                            <>
                                <div className={cardClass}>
                                    <h3 className="text-lg font-semibold text-white">
                                        Organization Overview
                                    </h3>
                                    <dl className="mt-5 space-y-3 text-sm text-zinc-400">
                                        <div className="flex items-center justify-between gap-4">
                                            <dt>Organization</dt>
                                            <dd className="font-semibold text-white">
                                                {managedOrganization.name}
                                            </dd>
                                        </div>
                                        <div className="flex items-center justify-between gap-4">
                                            <dt>Admins</dt>
                                            <dd className="font-semibold text-white">
                                                {managedOrganization.admins_count}
                                            </dd>
                                        </div>
                                        <div className="flex items-center justify-between gap-4">
                                            <dt>Tests</dt>
                                            <dd className="font-semibold text-white">
                                                {managedOrganization.tests_count}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <div className={cardClass}>
                                    <h3 className="text-lg font-semibold text-white">
                                        Organization Access
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-zinc-400">
                                        Open your organization profile, update details,
                                        and create admin accounts for your team.
                                    </p>
                                    <div className="mt-6 flex flex-wrap gap-3">
                                        <Link
                                            href={route(
                                                'super-admin.organizations.show',
                                                managedOrganization.id,
                                            )}
                                            className={primaryLinkClass}
                                        >
                                            View organization
                                        </Link>
                                        <Link
                                            href={route(
                                                'super-admin.organizations.admins.create',
                                                managedOrganization.id,
                                            )}
                                            className={secondaryLinkClass}
                                        >
                                            Add admin
                                        </Link>
                                    </div>
                                </div>
                            </>
                        ) : (
                            <div className={cardClass}>
                                <h3 className="text-lg font-semibold text-white">
                                    Organizations
                                </h3>
                                <p className="mt-2 text-sm leading-relaxed text-zinc-400">
                                    Create and manage organizations for exam admins.
                                </p>
                                <div className="mt-6 flex flex-wrap gap-3">
                                    {canCreateOrganizations && (
                                        <Link
                                            href={route(
                                                'super-admin.organizations.create',
                                            )}
                                            className={primaryLinkClass}
                                        >
                                            Create organization
                                        </Link>
                                    )}
                                    <Link
                                        href={route(
                                            'super-admin.organizations.index',
                                        )}
                                        className={secondaryLinkClass}
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
