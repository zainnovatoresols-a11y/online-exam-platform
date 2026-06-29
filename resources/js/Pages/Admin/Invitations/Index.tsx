import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

type Test = {
    id: number;
    title: string;
    status: string;
    public_access_enabled: boolean;
    organization: { id: number; name: string } | null;
    creator: { id: number; name: string; email: string } | null;
};

type Invitation = {
    id: number;
    name: string | null;
    email: string;
    status: string;
    starts_at: string | null;
    expires_at: string | null;
    accepted_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Props = {
    test: Test;
    canCreateInvitation: boolean;
    public_url: string | null;
    invitations: {
        data: Invitation[];
        from: number | null;
        to: number | null;
        total: number;
        links: PaginationLink[];
    };
};

const sectionClass =
    'rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/20';
const infoPanelClass =
    'max-w-xl rounded-2xl border border-zinc-800 bg-zinc-950/70 p-4 text-sm text-zinc-400';
const primaryLinkClass =
    'inline-flex h-11 items-center justify-center rounded-xl bg-emerald-500 px-5 text-sm font-bold text-black transition hover:bg-emerald-400';
const secondaryButtonClass =
    'inline-flex h-10 min-w-24 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-950 px-4 text-sm font-bold text-zinc-300 transition hover:border-emerald-500 hover:text-emerald-300';
const dangerButtonClass =
    'inline-flex h-10 min-w-24 items-center justify-center rounded-xl border border-red-500/20 bg-red-500/15 px-4 text-sm font-bold text-red-200 transition hover:bg-red-500/25';
const tableWrapperClass =
    'dark-horizontal-scrollbar overflow-x-auto rounded-[18px] border border-zinc-800 bg-zinc-900 shadow-2xl shadow-black/20';
const tableHeadingClass =
    'whitespace-nowrap px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500';
const tableCellClass = 'whitespace-nowrap px-6 py-4 text-sm text-zinc-400';
const paginationButtonClass =
    'inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-zinc-800 bg-zinc-950 px-3 text-sm font-semibold text-zinc-300 transition hover:border-zinc-600 hover:text-white';
const paginationActiveClass =
    'border-emerald-500 bg-emerald-500 text-black hover:border-emerald-400 hover:bg-emerald-400 hover:text-black';
const paginationDisabledClass =
    'cursor-not-allowed border-zinc-900 bg-zinc-950/70 text-zinc-600';

export default function Index({
    test,
    canCreateInvitation,
    public_url,
    invitations,
}: Props) {
    const resend = (invitationId: number) => {
        router.post(
            route('admin.tests.invitations.resend', [test.id, invitationId]),
        );
    };

    const revoke = (invitationId: number) => {
        router.delete(
            route('admin.tests.invitations.revoke', [test.id, invitationId]),
        );
    };

    return (
        <AuthenticatedLayout
            theme="dark"
            header={
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                        Invitation Center
                    </p>
                    <h2 className="mt-2 text-xl font-semibold leading-tight text-white">
                        Candidate Invitations
                    </h2>
                </div>
            }
        >
            <Head title="Candidate Invitations" />

            <div className="bg-zinc-950 px-4 py-10 text-zinc-100 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div className={`flex flex-wrap items-start justify-between gap-4 ${sectionClass}`}>
                        <div>
                            <h3 className="text-2xl font-bold text-white">
                                {test.title}
                            </h3>
                            <p className="mt-1 text-sm text-zinc-500">
                                Status: {test.status}
                            </p>
                            <p className="mt-1 text-sm text-zinc-500">
                                Owner:{' '}
                                {test.organization?.name ??
                                    test.creator?.name ??
                                    'Solo admin'}
                            </p>
                        </div>
                        {test.public_access_enabled ? (
                            <div className={infoPanelClass}>
                                <p className="font-semibold text-white">
                                    Public test URL
                                </p>
                                <p className="mt-1 break-all">
                                    {public_url ?? 'Not generated yet'}
                                </p>
                                <p className="mt-2 text-xs text-zinc-500">
                                    Anyone with this URL can register after
                                    accepting the policy.
                                </p>
                            </div>
                        ) : (
                            <div className={infoPanelClass}>
                                <p className="font-semibold text-white">
                                    Invite-only access
                                </p>
                                <p className="mt-1">
                                    Public access is off for this test. Only
                                    emailed candidates from this invitation list
                                    can register.
                                </p>
                            </div>
                        )}

                        {canCreateInvitation && (
                            <Link
                                href={route(
                                    'admin.tests.invitations.create',
                                    test.id,
                                )}
                                className={primaryLinkClass}
                            >
                                Invite candidate
                            </Link>
                        )}
                    </div>

                    <div className={tableWrapperClass}>
                        <div className="min-w-[940px]">
                            <table className="min-w-full divide-y divide-zinc-800">
                                <thead className="bg-zinc-950/80">
                                    <tr>
                                        <th className={tableHeadingClass}>
                                            Candidate
                                        </th>
                                        <th className={tableHeadingClass}>
                                            Status
                                        </th>
                                        <th className={tableHeadingClass}>
                                            Starts
                                        </th>
                                        <th className={tableHeadingClass}>
                                            Expires
                                        </th>
                                        <th className="px-6 py-3" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-zinc-800 bg-zinc-900">
                                    {invitations.data.map((invitation) => (
                                        <tr key={invitation.id}>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                <div className="font-semibold text-white">
                                                    {invitation.name ?? 'Unnamed'}
                                                </div>
                                                <div className="text-zinc-500">
                                                    {invitation.email}
                                                </div>
                                            </td>
                                            <td className={tableCellClass}>
                                                <StatusBadge
                                                    value={invitation.status}
                                                />
                                            </td>
                                            <td className={tableCellClass}>
                                                {invitation.starts_at
                                                    ? formatDateTime(
                                                          invitation.starts_at,
                                                      )
                                                    : 'Available now'}
                                            </td>
                                            <td className={tableCellClass}>
                                                {invitation.expires_at
                                                    ? formatDateTime(
                                                          invitation.expires_at,
                                                      )
                                                    : 'No expiry'}
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm">
                                                {['pending', 'sent'].includes(
                                                    invitation.status,
                                                ) && (
                                                    <div className="flex justify-end gap-3">
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                resend(
                                                                    invitation.id,
                                                                )
                                                            }
                                                            className={secondaryButtonClass}
                                                        >
                                                            Resend
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                revoke(
                                                                    invitation.id,
                                                                )
                                                            }
                                                            className={dangerButtonClass}
                                                        >
                                                            Revoke
                                                        </button>
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                    {invitations.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={5}
                                                className="px-6 py-10 text-center text-sm text-zinc-500"
                                            >
                                                No invitations sent yet.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {invitations.total > 0 && (
                            <div className="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-800 bg-zinc-900 px-6 py-4 text-sm">
                                <p className="text-zinc-500">
                                    Showing {invitations.from ?? 0} to{' '}
                                    {invitations.to ?? 0} of{' '}
                                    {invitations.total} invitations
                                </p>

                                <div className="flex flex-wrap gap-2">
                                    {invitations.links.map((link, index) =>
                                        link.url ? (
                                            <Link
                                                key={`${link.label}-${index}`}
                                                href={link.url}
                                                preserveScroll
                                                className={
                                                    paginationButtonClass +
                                                    ' ' +
                                                    (link.active
                                                        ? paginationActiveClass
                                                        : '')
                                                }
                                            >
                                                {paginationLabel(link.label)}
                                            </Link>
                                        ) : (
                                            <span
                                                key={`${link.label}-${index}`}
                                                className={`${paginationButtonClass} ${paginationDisabledClass}`}
                                            >
                                                {paginationLabel(link.label)}
                                            </span>
                                        ),
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function StatusBadge({ value }: { value: string }) {
    const classes =
        value === 'accepted'
            ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200'
            : value === 'revoked'
              ? 'border-red-400/20 bg-red-400/10 text-red-200'
              : value === 'expired'
                ? 'border-zinc-600 bg-zinc-950 text-zinc-300'
                : 'border-amber-400/20 bg-amber-400/10 text-amber-200';

    return (
        <span
            className={`inline-flex min-w-24 justify-center rounded-full border px-2.5 py-1 text-xs font-semibold ${classes}`}
        >
            {formatLabel(value)}
        </span>
    );
}

function paginationLabel(label: string): string {
    return label
        .replace('&laquo;', '<')
        .replace('&raquo;', '>')
        .replace('Previous', 'Prev')
        .trim();
}

function formatLabel(value: string): string {
    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
