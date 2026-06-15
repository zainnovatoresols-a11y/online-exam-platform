import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const defaultPolicy = `Read all instructions carefully before starting the test.
Do not copy, paste, or use outside help during the test.
Do not switch tabs, open another browser, or use another device for answers.
Answer every question yourself and submit before the timer ends.
Once the test starts, the timer cannot be paused.`;

type CandidateField = 'phone' | 'stack_name';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        duration_minutes: 60,
        pass_mark: 50,
        starts_at: '',
        public_access_enabled: false,
        candidate_fields: ['phone'] as CandidateField[],
        policy_text: defaultPolicy,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('admin.tests.store'));
    };

    const toggleCandidateField = (field: CandidateField) => {
        if (data.candidate_fields.includes(field)) {
            setData(
                'candidate_fields',
                data.candidate_fields.filter((value) => value !== field),
            );
            return;
        }

        setData('candidate_fields', [...data.candidate_fields, field]);
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Create Test
                </h2>
            }
        >
            <Head title="Create Test" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg"
                    >
                        <Link
                            href={route('admin.tests.index')}
                            className="text-sm font-medium text-gray-600 underline"
                        >
                            Back to tests
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

                        <div>
                            <InputLabel
                                htmlFor="starts_at"
                                value="Start time"
                            />
                            <TextInput
                                id="starts_at"
                                type="datetime-local"
                                className="mt-1 block w-full"
                                value={data.starts_at}
                                onChange={(event) =>
                                    setData('starts_at', event.target.value)
                                }
                            />
                            <InputError
                                message={errors.starts_at}
                                className="mt-2"
                            />
                        </div>

                        <div className="rounded-md border border-gray-200 p-4">
                            <div className="flex items-start gap-3">
                                <Checkbox
                                    checked={data.public_access_enabled}
                                    onChange={(event) =>
                                        setData(
                                            'public_access_enabled',
                                            event.currentTarget.checked,
                                        )
                                    }
                                />
                                <div>
                                    <InputLabel
                                        value="Allow anyone with the public URL"
                                    />
                                    <p className="mt-1 text-sm text-gray-600">
                                        When this is off, only emailed/invited
                                        addresses can register through the
                                        public test link.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-md border border-gray-200 p-4">
                            <InputLabel value="Required candidate fields" />
                            <p className="mt-1 text-sm text-gray-600">
                                Name and email are always required.
                            </p>
                            <div className="mt-4 space-y-3">
                                <label className="flex items-center gap-3 text-sm text-gray-700">
                                    <Checkbox
                                        checked={data.candidate_fields.includes('phone')}
                                        onChange={() => toggleCandidateField('phone')}
                                    />
                                    Phone
                                </label>
                                <label className="flex items-center gap-3 text-sm text-gray-700">
                                    <Checkbox
                                        checked={data.candidate_fields.includes('stack_name')}
                                        onChange={() => toggleCandidateField('stack_name')}
                                    />
                                    Stack / Skill
                                </label>
                            </div>
                            <InputError
                                message={errors.candidate_fields}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="policy_text"
                                value="Candidate policy and guidelines"
                            />
                            <textarea
                                id="policy_text"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                rows={7}
                                value={data.policy_text}
                                onChange={(event) =>
                                    setData('policy_text', event.target.value)
                                }
                            />
                            <InputError
                                message={errors.policy_text}
                                className="mt-2"
                            />
                        </div>

                        <div className="flex items-center gap-4">
                            <PrimaryButton disabled={processing}>
                                Save
                            </PrimaryButton>
                            <Link
                                href={route('admin.tests.index')}
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
