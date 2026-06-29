import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Test = {
    id: number;
    title: string;
    public_access_enabled: boolean;
    organization: { id: number; name: string } | null;
    creator: { id: number; name: string; email: string } | null;
};

type Props = {
    test: Test;
    public_url: string | null;
};

type InvitationForm = {
    emails: string;
    email_csv: File | null;
    starts_at: string;
    expires_at: string;
};

const sectionClass =
    'rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/20';
const formSectionClass = `${sectionClass} space-y-7`;
const labelClass = 'text-zinc-300';
const fieldClass =
    '!rounded-xl !border-zinc-700 !bg-zinc-950 !text-zinc-100 !shadow-none outline-none transition placeholder:!text-zinc-600 focus:!border-emerald-500 focus:!ring-2 focus:!ring-emerald-500/30';
const fileFieldClass =
    'mt-1 block w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 shadow-none outline-none transition file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-500 file:px-3 file:py-2 file:text-sm file:font-bold file:text-black hover:file:bg-emerald-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30';
const primaryButtonClass =
    '!h-11 !min-w-44 !justify-center !rounded-xl !bg-emerald-500 !px-5 !py-0 !text-sm !font-bold !tracking-normal !text-black hover:!bg-emerald-400 focus:!bg-emerald-400 focus:!ring-emerald-500/40 focus:!ring-offset-zinc-950 active:!bg-emerald-500 disabled:!opacity-60';
const secondaryLinkClass =
    'inline-flex h-11 min-w-32 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-950 px-5 text-sm font-bold text-zinc-300 transition hover:border-emerald-500 hover:text-emerald-300';
const infoPanelClass =
    'rounded-2xl border border-zinc-800 bg-zinc-950/70 p-4 text-sm text-zinc-400';

export default function Create({ test, public_url }: Props) {
    const { data, setData, post, processing, errors } =
        useForm<InvitationForm>({
            emails: '',
            email_csv: null,
            starts_at: '',
            expires_at: '',
        });

    const hasInvitationInput = data.emails.trim() !== '' || data.email_csv !== null;
    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('admin.tests.invitations.store', test.id));
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
                        Invite Candidates
                    </h2>
                </div>
            }
        >
            <Head title="Invite Candidates" />

            <div className="bg-zinc-950 px-4 py-10 text-zinc-100 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-3xl space-y-6">
                    <Link
                        href={route(
                            'admin.tests.invitations.index',
                            test.id,
                        )}
                        className={secondaryLinkClass}
                    >
                        Back to invitations
                    </Link>
                    <form
                        onSubmit={submit}
                        className={formSectionClass}
                    >
                        <div>
                            <h3 className="text-2xl font-bold text-white">
                                {test.title}
                            </h3>
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
                                    Public access is off for this test. Queue
                                    invitations below and only invited email
                                    addresses can register.
                                </p>
                            </div>
                        )}

                        <div className="space-y-2">
                            <InputLabel
                                htmlFor="emails"
                                value="Bulk emails"
                                className={labelClass}
                            />
                            <textarea
                                id="emails"
                                className={`block w-full ${fieldClass}`}
                                rows={8}
                                value={data.emails}
                                onChange={(event) =>
                                    setData('emails', event.target.value)
                                }
                                placeholder="candidate1@example.com&#10;candidate2@example.com"
                            />
                            <InputError
                                message={errors.emails}
                                className="mt-2"
                            />
                        </div>

                        <div className="space-y-2">
                            <InputLabel
                                htmlFor="email_csv"
                                value="CSV email file"
                                className={labelClass}
                            />
                            <input
                                id="email_csv"
                                type="file"
                                accept=".csv,text/csv,text/plain"
                                className={fileFieldClass}
                                onChange={(event) =>
                                    setData(
                                        'email_csv',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            <p className="text-xs leading-relaxed text-zinc-500">
                                CSV rows with valid emails are queued. Invalid
                                rows are skipped and reported after submit.
                            </p>
                            <InputError
                                message={errors.email_csv}
                                className="mt-2"
                            />
                        </div>

                        <div className="space-y-2 pt-1">
                            <InputLabel
                                htmlFor="starts_at"
                                value="Candidate start time"
                                className={labelClass}
                            />
                            <TextInput
                                id="starts_at"
                                type="datetime-local"
                                className={`block w-full ${fieldClass}`}
                                value={data.starts_at}
                                onChange={(event) =>
                                    setData('starts_at', event.target.value)
                                }
                                required
                            />
                            <InputError
                                message={errors.starts_at}
                                className="mt-2"
                            />
                        </div>

                        <div className="space-y-2">
                            <InputLabel
                                htmlFor="expires_at"
                                value="Invitation expires at"
                                className={labelClass}
                            />
                            <TextInput
                                id="expires_at"
                                type="datetime-local"
                                className={`block w-full ${fieldClass}`}
                                value={data.expires_at}
                                onChange={(event) =>
                                    setData('expires_at', event.target.value)
                                }
                            />
                            <InputError
                                message={errors.expires_at}
                                className="mt-2"
                            />
                        </div>

                        <div className="flex flex-wrap items-center gap-4 pt-2">
                            <PrimaryButton
                                disabled={processing || !hasInvitationInput}
                                className={primaryButtonClass}
                            >
                                Queue invitations
                            </PrimaryButton>
                            <Link
                                href={route(
                                    'admin.tests.invitations.index',
                                    test.id,
                                )}
                                className={secondaryLinkClass}
                            >
                                Cancel
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
