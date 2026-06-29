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

const labelClass = 'text-zinc-300';
const fieldClass =
    '!rounded-xl !border-zinc-700 !bg-zinc-950 !text-zinc-100 !shadow-none outline-none transition placeholder:!text-zinc-600 focus:!border-emerald-500 focus:!ring-2 focus:!ring-emerald-500/30';
const primaryButtonClass =
    '!rounded-xl !bg-emerald-500 !text-black hover:!bg-emerald-400 focus:!bg-emerald-400 focus:!ring-emerald-500/40 focus:!ring-offset-zinc-950 active:!bg-emerald-500 disabled:!opacity-60';
const backLinkClass =
    'mb-5 inline-flex h-10 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-950 px-4 text-sm font-semibold text-zinc-300 transition hover:border-emerald-500 hover:text-emerald-300';

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
            theme="dark"
            header={
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                        Question Bank
                    </p>
                    <h2 className="mt-2 text-xl font-semibold leading-tight text-white">
                        Create MCQ Question
                    </h2>
                </div>
            }
        >
            <Head title="Create MCQ Question" />

            <div className="bg-zinc-950 px-4 py-10 text-zinc-100 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-4xl">
                    <Link
                        href={route('admin.tests.questions.index', test.id)}
                        className={backLinkClass}
                    >
                        Back to questions
                    </Link>

                    <form
                        onSubmit={submit}
                        className="space-y-6 rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/20"
                    >
                        <p className="text-sm text-zinc-400">
                            Test: {test.title}
                        </p>

                        <div>
                            <InputLabel
                                htmlFor="body"
                                value="Question"
                                className={labelClass}
                            />
                            <textarea
                                id="body"
                                className={`mt-1 block w-full ${fieldClass}`}
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
                            <InputLabel
                                htmlFor="marks"
                                value="Marks"
                                className={labelClass}
                            />
                            <TextInput
                                id="marks"
                                type="number"
                                min="1"
                                className={`mt-1 block w-40 ${fieldClass}`}
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
                                <InputLabel
                                    value="Options"
                                    className={labelClass}
                                />
                                <button
                                    type="button"
                                    onClick={addOption}
                                    className="text-sm font-semibold text-emerald-300 underline-offset-4 transition hover:text-emerald-200 hover:underline"
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
                                    className="grid gap-3 rounded-2xl border border-zinc-800 bg-zinc-950/70 p-4 sm:grid-cols-[1fr_auto_auto]"
                                >
                                    <div>
                                        <TextInput
                                            className={`block w-full ${fieldClass}`}
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

                                    <label className="flex items-center gap-2 text-sm font-medium text-zinc-300">
                                        <input
                                            type="radio"
                                            className="sr-only"
                                            checked={option.is_correct}
                                            onChange={() => markCorrect(index)}
                                        />
                                        <span
                                            aria-hidden="true"
                                            className={`flex h-4 w-4 items-center justify-center rounded-full border transition ${
                                                option.is_correct
                                                    ? 'border-emerald-400 bg-emerald-500'
                                                    : 'border-zinc-500 bg-zinc-950'
                                            }`}
                                        >
                                            {option.is_correct && (
                                                <span className="h-1.5 w-1.5 rounded-full bg-black" />
                                            )}
                                        </span>
                                        Correct
                                    </label>

                                    <button
                                        type="button"
                                        onClick={() => removeOption(index)}
                                        className="text-sm font-semibold text-red-300 underline-offset-4 transition hover:text-red-200 hover:underline disabled:text-zinc-600"
                                        disabled={data.options.length <= 2}
                                    >
                                        Remove
                                    </button>
                                </div>
                            ))}
                        </div>

                        <div className="flex items-center gap-4">
                            <PrimaryButton
                                disabled={processing}
                                className={primaryButtonClass}
                            >
                                Save question
                            </PrimaryButton>
                            <Link
                                href={route(
                                    'admin.tests.questions.index',
                                    test.id,
                                )}
                                className="text-sm font-semibold text-zinc-400 underline-offset-4 transition hover:text-white hover:underline"
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
