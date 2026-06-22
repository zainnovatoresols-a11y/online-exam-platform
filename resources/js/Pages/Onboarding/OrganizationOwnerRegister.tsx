import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import PublicEntryLayout from '@/Layouts/PublicEntryLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const benefits = [
    'Creates the organization record and owner account in one step.',
    'Lets you manage your own organization details after sign-in.',
    'Supports adding more admins inside the same organization later.',
];

export default function OrganizationOwnerRegister() {
    const { data, setData, post, processing, errors, reset } = useForm({
        organization_name: '',
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('onboarding.organization-owner.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Organization Owner Onboarding" />

            <PublicEntryLayout
                eyebrow="Organization Setup"
                title="Create your organization workspace and owner account."
                description="Use this path when your exam operations belong to a shared organization and more admins may need access after setup."
                supportingContent={
                    <div className="space-y-6">
                        <div className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                            <p className="text-sm font-semibold text-zinc-950">
                                What happens after signup
                            </p>
                            <p className="mt-2 text-sm leading-6 text-zinc-600">
                                You will land on the organization owner
                                dashboard, where you can review your workspace
                                and add admins for your team.
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
                            Owner access
                        </p>
                        <h2 className="mt-3 text-2xl font-semibold tracking-tight text-zinc-950">
                            Create your organization owner account
                        </h2>
                        <p className="mt-2 text-sm leading-6 text-zinc-600">
                            This step sets the organization name and the first
                            owner login for that workspace.
                        </p>
                    </div>

                    <form onSubmit={submit} className="mt-6 space-y-6">
                        <div className="space-y-4">
                            <div>
                                <h3 className="text-sm font-semibold uppercase tracking-[0.18em] text-zinc-500">
                                    Organization details
                                </h3>
                                <p className="mt-1 text-sm text-zinc-600">
                                    Name the workspace your admins will manage.
                                </p>
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="organization_name"
                                    value="Organization name"
                                />
                                <TextInput
                                    id="organization_name"
                                    value={data.organization_name}
                                    className="mt-1 block w-full"
                                    placeholder="Acme Institute"
                                    isFocused={true}
                                    onChange={(event) =>
                                        setData(
                                            'organization_name',
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.organization_name}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div className="space-y-4 border-t border-zinc-200 pt-6">
                            <div>
                                <h3 className="text-sm font-semibold uppercase tracking-[0.18em] text-zinc-500">
                                    Owner account
                                </h3>
                                <p className="mt-1 text-sm text-zinc-600">
                                    This login will become the first super admin
                                    for the organization.
                                </p>
                            </div>

                            <div>
                                <InputLabel htmlFor="name" value="Your name" />
                                <TextInput
                                    id="name"
                                    value={data.name}
                                    className="mt-1 block w-full"
                                    autoComplete="name"
                                    placeholder="Ayesha Khan"
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
                                    placeholder="owner@acme.com"
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
                                Create organization owner account
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </PublicEntryLayout>
        </>
    );
}
