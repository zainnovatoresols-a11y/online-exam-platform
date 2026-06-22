import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import PublicEntryLayout from '@/Layouts/PublicEntryLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const benefits = [
    'No organization setup is created with this path.',
    'You can start building solo tests immediately after sign-in.',
    'Best for one-admin hiring, training, or evaluation workflows.',
];

export default function SoloAdminRegister() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('onboarding.solo-admin.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Solo Admin Onboarding" />

            <PublicEntryLayout
                eyebrow="Solo Setup"
                title="Create a standalone admin account for independent test operations."
                description="Use this path when one admin owns the assessment workflow and does not need an organization workspace."
                supportingContent={
                    <div className="space-y-6">
                        <div className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                            <p className="text-sm font-semibold text-zinc-950">
                                Best fit
                            </p>
                            <p className="mt-2 text-sm leading-6 text-zinc-600">
                                Solo admin access works well for independent
                                recruiters, internal evaluators, trainers, and
                                small teams that do not need multi-admin
                                organization setup.
                            </p>
                        </div>

                        <div className="space-y-4">
                            {benefits.map((benefit, index) => (
                                <div key={benefit} className="flex gap-4">
                                    <div className="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-zinc-300 bg-white text-sm font-semibold text-zinc-700">
                                        {index + 1}
                                    </div>
                                    <p className="text-sm leading-6 text-zinc-600">
                                        {benefit}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                }
            >
                <div className="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
                    <div className="border-b border-zinc-200 pb-6">
                        <p className="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500">
                            Solo admin access
                        </p>
                        <h2 className="mt-3 text-2xl font-semibold tracking-tight text-zinc-950">
                            Create your standalone admin account
                        </h2>
                        <p className="mt-2 text-sm leading-6 text-zinc-600">
                            This setup signs you in directly as an admin without
                            creating an organization record.
                        </p>
                    </div>

                    <form onSubmit={submit} className="mt-6 space-y-6">
                        <div className="space-y-4">
                            <div>
                                <h3 className="text-sm font-semibold uppercase tracking-[0.18em] text-zinc-500">
                                    Admin account
                                </h3>
                                <p className="mt-1 text-sm text-zinc-600">
                                    Use the details you want tied to your solo
                                    test operations.
                                </p>
                            </div>

                            <div>
                                <InputLabel htmlFor="name" value="Name" />
                                <TextInput
                                    id="name"
                                    value={data.name}
                                    className="mt-1 block w-full"
                                    autoComplete="name"
                                    placeholder="Ayesha Khan"
                                    isFocused={true}
                                    onChange={(event) =>
                                        setData('name', event.target.value)
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel htmlFor="email" value="Email" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    className="mt-1 block w-full"
                                    autoComplete="email"
                                    placeholder="admin@example.com"
                                    onChange={(event) =>
                                        setData('email', event.target.value)
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.email}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel
                                        htmlFor="password"
                                        value="Password"
                                    />
                                    <TextInput
                                        id="password"
                                        type="password"
                                        value={data.password}
                                        className="mt-1 block w-full"
                                        autoComplete="new-password"
                                        onChange={(event) =>
                                            setData(
                                                'password',
                                                event.target.value,
                                            )
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.password}
                                        className="mt-2"
                                    />
                                </div>

                                <div>
                                    <InputLabel
                                        htmlFor="password_confirmation"
                                        value="Confirm password"
                                    />
                                    <TextInput
                                        id="password_confirmation"
                                        type="password"
                                        value={data.password_confirmation}
                                        className="mt-1 block w-full"
                                        autoComplete="new-password"
                                        onChange={(event) =>
                                            setData(
                                                'password_confirmation',
                                                event.target.value,
                                            )
                                        }
                                        required
                                    />
                                    <InputError
                                        message={
                                            errors.password_confirmation
                                        }
                                        className="mt-2"
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-col gap-3 border-t border-zinc-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                            <Link
                                href={route('onboarding.index')}
                                className="text-sm font-medium text-zinc-600 underline underline-offset-4"
                            >
                                Back to account choices
                            </Link>

                            <PrimaryButton disabled={processing}>
                                Create solo admin account
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </PublicEntryLayout>
        </>
    );
}
