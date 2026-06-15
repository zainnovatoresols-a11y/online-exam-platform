import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Test = {
    id: number;
    title: string;
    organization: { id: number; name: string } | null;
    creator: { id: number; name: string; email: string } | null;
};

type Candidate = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    stack_name: string | null;
};

type Props = {
    test: Test;
    public_url: string | null;
    candidates: Candidate[];
    stacks: string[];
    filters: {
        stack: string | null;
    };
};

type InvitationForm = {
    candidate_ids: number[];
    emails: string;
    starts_at: string;
    expires_at: string;
};

export default function Create({
    test,
    public_url,
    candidates,
    stacks,
    filters,
}: Props) {
    const { data, setData, post, processing, errors } =
        useForm<InvitationForm>({
            candidate_ids: [],
            emails: '',
            starts_at: '',
            expires_at: '',
        });

    const visibleIds = candidates.map((candidate) => candidate.id);
    const allVisibleSelected =
        visibleIds.length > 0 &&
        visibleIds.every((id) => data.candidate_ids.includes(id));

    const changeStack = (stack: string) => {
        router.get(
            route('admin.tests.invitations.create', test.id),
            stack ? { stack } : {},
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const toggleCandidate = (candidateId: number) => {
        if (data.candidate_ids.includes(candidateId)) {
            setData(
                'candidate_ids',
                data.candidate_ids.filter((id) => id !== candidateId),
            );

            return;
        }

        setData('candidate_ids', [...data.candidate_ids, candidateId]);
    };

    const toggleVisibleCandidates = () => {
        if (allVisibleSelected) {
            setData(
                'candidate_ids',
                data.candidate_ids.filter((id) => !visibleIds.includes(id)),
            );

            return;
        }

        setData('candidate_ids', Array.from(new Set([...data.candidate_ids, ...visibleIds])));
    };

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
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
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
                        <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                            <div className="space-y-4">
                                <div className="flex flex-wrap items-center justify-between gap-4">
                                    <div>
                                        <InputLabel
                                            htmlFor="stack"
                                            value="Filter by stack"
                                        />
                                        <select
                                            id="stack"
                                            value={filters.stack ?? ''}
                                            onChange={(event) =>
                                                changeStack(event.target.value)
                                            }
                                            className="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">All stacks</option>
                                            {stacks.map((stack) => (
                                                <option
                                                    key={stack}
                                                    value={stack}
                                                >
                                                    {stack}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <button
                                        type="button"
                                        onClick={toggleVisibleCandidates}
                                        disabled={visibleIds.length === 0}
                                        className="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {allVisibleSelected
                                            ? 'Clear visible'
                                            : 'Select visible'}
                                    </button>
                                </div>

                                <InputError
                                    message={errors.candidate_ids}
                                    className="mt-2"
                                />

                                <div className="overflow-hidden border border-gray-200 sm:rounded-lg">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="w-12 px-6 py-3" />
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                                    Candidate
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                                    Phone
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                                    Stack
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 bg-white">
                                            {candidates.map((candidate) => (
                                                <tr key={candidate.id}>
                                                    <td className="px-6 py-4">
                                                        <Checkbox
                                                            checked={data.candidate_ids.includes(
                                                                candidate.id,
                                                            )}
                                                            onChange={() =>
                                                                toggleCandidate(
                                                                    candidate.id,
                                                                )
                                                            }
                                                        />
                                                    </td>
                                                    <td className="px-6 py-4 text-sm">
                                                        <div className="font-medium text-gray-900">
                                                            {candidate.name}
                                                        </div>
                                                        <div className="text-gray-600">
                                                            {candidate.email}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-600">
                                                        {candidate.phone ?? '-'}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-600">
                                                        {candidate.stack_name ??
                                                            '-'}
                                                    </td>
                                                </tr>
                                            ))}
                                            {candidates.length === 0 && (
                                                <tr>
                                                    <td
                                                        colSpan={4}
                                                        className="px-6 py-4 text-sm text-gray-600"
                                                    >
                                                        No candidates found for this filter.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>

                                {candidates.length === 0 && (
                                    <Link
                                        href={route('admin.candidates.create')}
                                        className="inline-flex text-sm font-medium text-gray-900 underline"
                                    >
                                        Add candidates
                                    </Link>
                                )}
                            </div>

                            <div className="space-y-6">
                                <div className="rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                                    <p className="font-medium text-gray-900">
                                        Public test URL
                                    </p>
                                    <p className="mt-1 break-all">
                                        {public_url ?? 'Not generated yet'}
                                    </p>
                                    <p className="mt-2 text-xs text-gray-500">
                                        Emailed candidates receive this same
                                        public link. If public access is off,
                                        only invited emails can register.
                                    </p>
                                </div>

                                <div>
                                    <InputLabel htmlFor="emails" value="Bulk emails" />
                                    <textarea
                                        id="emails"
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        rows={6}
                                        value={data.emails}
                                        onChange={(event) => setData('emails', event.target.value)}
                                        placeholder="candidate1@example.com&#10;candidate2@example.com"
                                    />
                                    <InputError message={errors.emails} className="mt-2" />
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
                                            setData(
                                                'starts_at',
                                                event.target.value,
                                            )
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
                                            setData(
                                                'expires_at',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={errors.expires_at}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                                    Selected candidates:{' '}
                                    <span className="font-semibold">
                                        {data.candidate_ids.length}
                                    </span>
                                </div>

                                <div className="flex flex-wrap items-center gap-4">
                                    <PrimaryButton
                                        disabled={
                                            processing ||
                                            (data.candidate_ids.length === 0 &&
                                                data.emails.trim() === '')
                                        }
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
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
