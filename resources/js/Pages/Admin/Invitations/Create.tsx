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
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Invite Candidates
                </h2>
            }
        >
            <Head title="Invite Candidates" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <Link
                            href={route(
                                'admin.tests.invitations.index',
                                test.id,
                            )}
                            className="text-sm font-medium text-gray-600 underline"
                        >
                            Back to invitations
                        </Link>

                        <h3 className="mt-4 text-lg font-semibold text-gray-900">
                            {test.title}
                        </h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Owner:{' '}
                            {test.organization?.name ??
                                test.creator?.name ??
                                'Solo admin'}
                        </p>
                    </div>

                    <form
                        onSubmit={submit}
                        className="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg"
                    >
                        <div className="rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                            <p className="font-medium text-gray-900">
                                Public test URL
                            </p>
                            <p className="mt-1 break-all">
                                {public_url ?? 'Not generated yet'}
                            </p>
                            <p className="mt-2 text-xs text-gray-500">
                                Emailed candidates receive this same public
                                link. If public access is off, only invited
                                emails can register.
                            </p>
                        </div>

                        <div>
                            <InputLabel htmlFor="emails" value="Bulk emails" />
                            <textarea
                                id="emails"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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

                        <div>
                            <InputLabel
                                htmlFor="email_csv"
                                value="CSV email file"
                            />
                            <input
                                id="email_csv"
                                type="file"
                                accept=".csv,text/csv,text/plain"
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 focus:border-indigo-500 focus:ring-indigo-500"
                                onChange={(event) =>
                                    setData(
                                        'email_csv',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            <p className="mt-2 text-xs text-gray-500">
                                CSV rows with valid emails are queued. Invalid
                                rows are skipped and reported after submit.
                            </p>
                            <InputError
                                message={errors.email_csv}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="starts_at"
                                value="Candidate start time"
                            />
                            <TextInput
                                id="starts_at"
                                type="datetime-local"
                                className="mt-1 block w-full"
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

                        <div>
                            <InputLabel
                                htmlFor="expires_at"
                                value="Invitation expires at"
                            />
                            <TextInput
                                id="expires_at"
                                type="datetime-local"
                                className="mt-1 block w-full"
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

                        <div className="flex flex-wrap items-center gap-4">
                            <PrimaryButton
                                disabled={processing || !hasInvitationInput}
                            >
                                Queue invitations
                            </PrimaryButton>
                            <Link
                                href={route(
                                    'admin.tests.invitations.index',
                                    test.id,
                                )}
                                className="text-sm text-gray-600 underline"
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
