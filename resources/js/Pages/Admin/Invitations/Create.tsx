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

export default function Create({ test }: { test: Test }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        expires_at: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('admin.tests.invitations.store', test.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Invite Candidate
                </h2>
            }
        >
            <Head title="Invite Candidate" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg"
                    >
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900">
                                {test.title}
                            </h3>
                            <p className="mt-1 text-sm text-gray-600">
                                Owner:{' '}
                                {test.organization?.name ??
                                    test.creator?.name ??
                                    'Solo admin'}
                            </p>
                        </div>

                        <div>
                            <InputLabel htmlFor="name" value="Name" />
                            <TextInput
                                id="name"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
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

                        <div>
                            <InputLabel
                                htmlFor="expires_at"
                                value="Expires at"
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

                        <div className="flex items-center gap-4">
                            <PrimaryButton disabled={processing}>
                                Send invitation
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
