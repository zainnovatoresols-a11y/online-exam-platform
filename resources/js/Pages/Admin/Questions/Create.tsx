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
};

type OptionForm = {
    body: string;
    is_correct: boolean;
};

type QuestionForm = {
    body: string;
    marks: string;
    options: OptionForm[];
};

export default function Create({ test }: { test: Test }) {
    const { data, setData, post, processing, errors } = useForm<QuestionForm>({
        body: '',
        marks: '1',
        options: [
            { body: '', is_correct: true },
            { body: '', is_correct: false },
        ],
    });

    const formErrors = errors as Record<string, string>;

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('admin.tests.questions.store', test.id));
    };

    const updateOptionBody = (index: number, body: string) => {
        setData(
            'options',
            data.options.map((option, optionIndex) =>
                optionIndex === index ? { ...option, body } : option,
            ),
        );
    };

    const markCorrect = (index: number) => {
        setData(
            'options',
            data.options.map((option, optionIndex) => ({
                ...option,
                is_correct: optionIndex === index,
            })),
        );
    };

    const addOption = () => {
        setData('options', [...data.options, { body: '', is_correct: false }]);
    };

    const removeOption = (index: number) => {
        if (data.options.length <= 2) {
            return;
        }

        const nextOptions = data.options.filter(
            (_option, optionIndex) => optionIndex !== index,
        );

        if (!nextOptions.some((option) => option.is_correct)) {
            nextOptions[0].is_correct = true;
        }

        setData('options', nextOptions);
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Create MCQ Question
                </h2>
            }
        >
            <Head title="Create MCQ Question" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg"
                    >
                        <p className="text-sm text-gray-600">
                            Test: {test.title}
                        </p>

                        <div>
                            <InputLabel htmlFor="body" value="Question" />
                            <textarea
                                id="body"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                rows={4}
                                value={data.body}
                                onChange={(event) =>
                                    setData('body', event.target.value)
                                }
                                required
                            />
                            <InputError message={errors.body} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="marks" value="Marks" />
                            <TextInput
                                id="marks"
                                type="number"
                                min="1"
                                className="mt-1 block w-40"
                                value={data.marks}
                                onChange={(event) =>
                                    setData('marks', event.target.value)
                                }
                                required
                            />
                            <InputError message={errors.marks} className="mt-2" />
                        </div>

                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <InputLabel value="Options" />
                                <button
                                    type="button"
                                    onClick={addOption}
                                    className="text-sm font-medium text-gray-900 underline"
                                >
                                    Add option
                                </button>
                            </div>
                            <InputError
                                message={formErrors.options}
                                className="mt-2"
                            />

                            {data.options.map((option, index) => (
                                <div
                                    key={index}
                                    className="grid gap-3 rounded-md border border-gray-200 p-4 sm:grid-cols-[1fr_auto_auto]"
                                >
                                    <div>
                                        <TextInput
                                            className="block w-full"
                                            value={option.body}
                                            onChange={(event) =>
                                                updateOptionBody(
                                                    index,
                                                    event.target.value,
                                                )
                                            }
                                            required
                                        />
                                        <InputError
                                            message={
                                                formErrors[
                                                    `options.${index}.body`
                                                ]
                                            }
                                            className="mt-2"
                                        />
                                    </div>

                                    <label className="flex items-center gap-2 text-sm text-gray-700">
                                        <input
                                            type="radio"
                                            checked={option.is_correct}
                                            onChange={() => markCorrect(index)}
                                        />
                                        Correct
                                    </label>

                                    <button
                                        type="button"
                                        onClick={() => removeOption(index)}
                                        className="text-sm text-red-600 underline disabled:text-gray-400"
                                        disabled={data.options.length <= 2}
                                    >
                                        Remove
                                    </button>
                                </div>
                            ))}
                        </div>

                        <div className="flex items-center gap-4">
                            <PrimaryButton disabled={processing}>
                                Save question
                            </PrimaryButton>
                            <Link
                                href={route(
                                    'admin.tests.questions.index',
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
