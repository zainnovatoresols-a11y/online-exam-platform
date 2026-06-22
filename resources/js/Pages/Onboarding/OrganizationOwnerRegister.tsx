import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

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
        <GuestLayout>
            <Head title="Organization Owner Onboarding" />

            <form onSubmit={submit} className="space-y-4">
                <div className="mb-2">
                    <h1 className="text-xl font-semibold text-gray-900">
                        Create organization owner account
                    </h1>
                    <p className="mt-2 text-sm text-gray-600">
                        This creates your organization and signs you in as its
                        super admin.
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
                        isFocused={true}
                        onChange={(event) =>
                            setData('organization_name', event.target.value)
                        }
                        required
                    />
                    <InputError
                        message={errors.organization_name}
                        className="mt-2"
                    />
                </div>

                <div>
                    <InputLabel htmlFor="name" value="Your name" />
                    <TextInput
                        id="name"
                        value={data.name}
                        className="mt-1 block w-full"
                        autoComplete="name"
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
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        onChange={(event) =>
                            setData('email', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="password" value="Password" />
                    <TextInput
                        id="password"
                        type="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(event) =>
                            setData('password', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.password} className="mt-2" />
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
                        message={errors.password_confirmation}
                        className="mt-2"
                    />
                </div>

                <div className="flex items-center justify-between gap-4 pt-2">
                    <Link
                        href={route('onboarding.index')}
                        className="text-sm text-gray-600 underline"
                    >
                        Back
                    </Link>

                    <PrimaryButton disabled={processing}>
                        Create account
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
