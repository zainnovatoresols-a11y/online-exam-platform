import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type CandidateField = 'phone' | 'stack_name';

type Test = {
    title: string;
    public_token: string;
    candidate_fields: CandidateField[];
    public_access_enabled: boolean;
    organization: { id: number; name: string } | null;
    creator: { id: number; name: string; email: string } | null;
};

export default function Register({
    test,
    email,
    invitation_token,
}: {
    test: Test;
    email: string;
    invitation_token: string | null;
}) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email,
        invitation_token: invitation_token ?? '',
        phone: '',
        stack_name: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('candidate.public-tests.register.store', test.public_token));
    };

    const requires = (field: CandidateField) =>
        test.candidate_fields.includes(field);

    return (
        <GuestLayout>
            <Head title="Candidate Details" />

            <form onSubmit={submit} className="space-y-6">
                <div>
                    <p className="text-sm font-medium uppercase text-gray-500">
                        Candidate details
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold text-gray-900">
                        {test.title}
                    </h1>
                    <p className="mt-2 text-sm text-gray-600">
                        From:{' '}
                        {test.organization?.name ??
                            test.creator?.name ??
                            'Exam Admin'}
                    </p>
                </div>

                {!test.public_access_enabled && (
                    <div className="rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                        This test is restricted to invited email addresses.
                        Please enter the same email that received the test
                        email.
                    </div>
                )}

                <div>
                    <InputLabel htmlFor="name" value="Full name" />
                    <TextInput
                        id="name"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(event) =>
                            setData('name', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        className="mt-1 block w-full"
                        value={data.email}
                        onChange={(event) =>
                            setData('email', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <input
                    type="hidden"
                    value={data.invitation_token}
                    onChange={(event) =>
                        setData('invitation_token', event.target.value)
                    }
                />

                <div>
                    <InputLabel
                        htmlFor="phone"
                        value={`Phone${requires('phone') ? '' : ' (optional)'}`}
                    />
                    <TextInput
                        id="phone"
                        className="mt-1 block w-full"
                        value={data.phone}
                        onChange={(event) =>
                            setData('phone', event.target.value)
                        }
                        required={requires('phone')}
                    />
                    <InputError message={errors.phone} className="mt-2" />
                </div>

                <div>
                    <InputLabel
                        htmlFor="stack_name"
                        value={`Stack / Skill${requires('stack_name') ? '' : ' (optional)'}`}
                    />
                    <TextInput
                        id="stack_name"
                        className="mt-1 block w-full"
                        value={data.stack_name}
                        onChange={(event) =>
                            setData('stack_name', event.target.value)
                        }
                        required={requires('stack_name')}
                    />
                    <InputError message={errors.stack_name} className="mt-2" />
                </div>

                <PrimaryButton disabled={processing}>
                    Continue to test
                </PrimaryButton>
            </form>
        </GuestLayout>
    );
}
