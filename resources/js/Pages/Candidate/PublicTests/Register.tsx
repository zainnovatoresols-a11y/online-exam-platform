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

const inputClassName =
    'mt-1 block h-11 w-full !rounded-xl !border-zinc-700 !bg-zinc-950 !text-zinc-100 !shadow-none outline-none transition placeholder:!text-zinc-600 focus:!border-emerald-500 focus:!ring-2 focus:!ring-emerald-500/30';

const labelClassName = '!text-zinc-300';

const errorClassName = 'mt-2 !text-red-300';

const primaryActionClassName =
    'w-full justify-center !rounded-xl !border-emerald-500 !bg-emerald-500 !px-5 !py-2.5 !text-black hover:!bg-emerald-400 focus:!ring-emerald-500 focus:!ring-offset-zinc-950 disabled:!opacity-50 sm:w-auto';

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
        <GuestLayout theme="dark">
            <Head title="Candidate Details" />

            <form onSubmit={submit} className="space-y-6">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-300">
                        Candidate details
                    </p>
                    <h1 className="mt-3 text-2xl font-semibold text-white">
                        {test.title}
                    </h1>
                    <p className="mt-3 text-sm text-zinc-400">
                        From:{' '}
                        {test.organization?.name ??
                            test.creator?.name ??
                            'Exam Admin'}
                    </p>
                </div>

                {!test.public_access_enabled && (
                    <div className="rounded-xl border border-amber-400/20 bg-amber-400/10 p-4 text-sm text-amber-100">
                        This test is restricted to invited email addresses.
                        Please enter the same email that received the test
                        email.
                    </div>
                )}

                <div>
                    <InputLabel
                        htmlFor="name"
                        value="Full name"
                        className={labelClassName}
                    />
                    <TextInput
                        id="name"
                        className={inputClassName}
                        value={data.name}
                        onChange={(event) =>
                            setData('name', event.target.value)
                        }
                        required
                    />
                    <InputError
                        message={errors.name}
                        className={errorClassName}
                    />
                </div>

                <div>
                    <InputLabel
                        htmlFor="email"
                        value="Email"
                        className={labelClassName}
                    />
                    <TextInput
                        id="email"
                        type="email"
                        className={inputClassName}
                        value={data.email}
                        onChange={(event) =>
                            setData('email', event.target.value)
                        }
                        required
                    />
                    <InputError
                        message={errors.email}
                        className={errorClassName}
                    />
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
                        className={labelClassName}
                    />
                    <TextInput
                        id="phone"
                        className={inputClassName}
                        value={data.phone}
                        onChange={(event) =>
                            setData('phone', event.target.value)
                        }
                        required={requires('phone')}
                    />
                    <InputError
                        message={errors.phone}
                        className={errorClassName}
                    />
                </div>

                <div>
                    <InputLabel
                        htmlFor="stack_name"
                        value={`Stack / Skill${requires('stack_name') ? '' : ' (optional)'}`}
                        className={labelClassName}
                    />
                    <TextInput
                        id="stack_name"
                        className={inputClassName}
                        value={data.stack_name}
                        onChange={(event) =>
                            setData('stack_name', event.target.value)
                        }
                        required={requires('stack_name')}
                    />
                    <InputError
                        message={errors.stack_name}
                        className={errorClassName}
                    />
                </div>

                <PrimaryButton
                    disabled={processing}
                    className={primaryActionClassName}
                >
                    Continue to test
                </PrimaryButton>
            </form>
        </GuestLayout>
    );
}
