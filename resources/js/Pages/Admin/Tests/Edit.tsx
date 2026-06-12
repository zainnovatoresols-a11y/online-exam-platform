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
    description: string | null;
    duration_minutes: number;
    pass_mark: number;
};

export default function Edit({ test }: { test: Test }) {
    const { data, setData, patch, processing, errors } = useForm({
        title: test.title,
        description: test.description ?? '',
        duration_minutes: test.duration_minutes,
        pass_mark: test.pass_mark,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        patch(route('admin.tests.update', test.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Edit Test
                </h2>
            }
        >
            <Head title="Edit Test" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg"
                    >
                        <Link
                            href={route('admin.tests.show', test.id)}
                            className="text-sm font-medium text-gray-600 underline"
                        >
                            Back to test
                        </Link>

                        <div>
                            <InputLabel htmlFor="title" value="Title" />
                            <TextInput
                                id="title"
                                className="mt-1 block w-full"
                                value={data.title}
                                onChange={(event) =>
                                    setData('title', event.target.value)
                                }
                                required
                            />
                            <InputError message={errors.title} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="description"
                                value="Description"
                            />
                            <textarea
                                id="description"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                rows={4}
                                value={data.description}
                                onChange={(event) =>
                                    setData('description', event.target.value)
                                }
                            />
                            <InputError
                                message={errors.description}
                                className="mt-2"
                            />
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor="duration_minutes"
                                    value="Duration minutes"
                                />
                                <TextInput
                                    id="duration_minutes"
                                    type="number"
                                    min="1"
                                    className="mt-1 block w-full"
                                    value={data.duration_minutes}
                                    onChange={(event) =>
                                        setData(
                                            'duration_minutes',
                                            Number(event.target.value),
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.duration_minutes}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor="pass_mark"
                                    value="Pass mark"
                                />
                                <TextInput
                                    id="pass_mark"
                                    type="number"
                                    min="1"
                                    className="mt-1 block w-full"
                                    value={data.pass_mark}
                                    onChange={(event) =>
                                        setData(
                                            'pass_mark',
                                            Number(event.target.value),
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.pass_mark}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div className="flex items-center gap-4">
                            <PrimaryButton disabled={processing}>
                                Update
                            </PrimaryButton>
                            <Link
                                href={route('admin.tests.show', test.id)}
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
