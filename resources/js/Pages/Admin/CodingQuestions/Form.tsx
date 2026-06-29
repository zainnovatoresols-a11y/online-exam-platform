import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

type Test = {
    id: number;
    title: string;
};

type SelectOption = {
    value: string;
    label: string;
};

export type CodingTestCaseForm = {
    input: string;
    expected_output: string;
    is_hidden: boolean;
    points: string;
};

export type CodingQuestionFormData = {
    body: string;
    marks: string;
    order: string;
    difficulty: string;
    time_limit_minutes: string;
    supported_languages: string[];
    starter_code: Record<string, string>;
    test_cases: CodingTestCaseForm[];
};

type Props = {
    test: Test;
    difficulties: SelectOption[];
    languages: SelectOption[];
    initialData: CodingQuestionFormData;
    submitLabel: string;
    submitRoute: string;
    method: 'post' | 'patch';
};

const labelClass = 'text-zinc-300';
const fieldClass =
    '!rounded-xl !border-zinc-700 !bg-zinc-950 !text-zinc-100 !shadow-none outline-none transition placeholder:!text-zinc-600 focus:!border-emerald-500 focus:!ring-2 focus:!ring-emerald-500/30';
const codeFieldClass = `${fieldClass} font-mono text-sm`;
const primaryButtonClass =
    '!h-11 !min-w-40 !justify-center !rounded-xl !bg-emerald-500 !px-5 !py-0 !text-sm !font-bold !tracking-normal !text-black hover:!bg-emerald-400 focus:!bg-emerald-400 focus:!ring-emerald-500/40 focus:!ring-offset-zinc-950 active:!bg-emerald-500 disabled:!opacity-60';
const secondaryButtonClass =
    'inline-flex h-11 min-w-32 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-950 px-5 text-sm font-bold text-zinc-300 transition hover:border-emerald-500 hover:text-emerald-300';

export default function CodingQuestionForm({
    test,
    difficulties,
    languages,
    initialData,
    submitLabel,
    submitRoute,
    method,
}: Props) {
    const { data, setData, post, patch, processing, errors, transform } =
        useForm<CodingQuestionFormData>(initialData);
    const [isDifficultyOpen, setIsDifficultyOpen] = useState(false);

    const formErrors = errors as Record<string, string>;

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        transform((formData) => ({
            ...formData,
            time_limit_ms: minutesToMilliseconds(formData.time_limit_minutes),
        }));

        if (method === 'post') {
            post(submitRoute);
            return;
        }

        patch(submitRoute);
    };

    const toggleLanguage = (language: string) => {
        const selected = data.supported_languages.includes(language);
        const nextLanguages = selected
            ? data.supported_languages.filter((value) => value !== language)
            : [...data.supported_languages, language];
        const nextStarterCode = { ...data.starter_code };

        if (selected) {
            delete nextStarterCode[language];
        } else {
            nextStarterCode[language] = nextStarterCode[language] ?? '';
        }

        setData({
            ...data,
            supported_languages: nextLanguages,
            starter_code: nextStarterCode,
        });
    };

    const updateStarterCode = (language: string, value: string) => {
        setData('starter_code', {
            ...data.starter_code,
            [language]: value,
        });
    };

    const updateTestCase = (
        index: number,
        key: keyof CodingTestCaseForm,
        value: string | boolean,
    ) => {
        setData(
            'test_cases',
            data.test_cases.map((testCase, testCaseIndex) =>
                testCaseIndex === index
                    ? { ...testCase, [key]: value }
                    : testCase,
            ),
        );
    };

    const addTestCase = () => {
        setData('test_cases', [
            ...data.test_cases,
            {
                input: '',
                expected_output: '',
                is_hidden: false,
                points: '',
            },
        ]);
    };

    const removeTestCase = (index: number) => {
        if (data.test_cases.length <= 1) {
            return;
        }

        setData(
            'test_cases',
            data.test_cases.filter(
                (_testCase, testCaseIndex) => testCaseIndex !== index,
            ),
        );
    };

    const selectedLanguages = languages.filter((language) =>
        data.supported_languages.includes(language.value),
    );
    const selectedDifficulty =
        difficulties.find((difficulty) => difficulty.value === data.difficulty)
            ?.label ?? 'Select difficulty';

    const selectDifficulty = (value: string) => {
        setData('difficulty', value);
        setIsDifficultyOpen(false);
    };

    return (
        <form
            onSubmit={submit}
            className="space-y-6 rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/20"
        >
            <p className="text-sm text-zinc-400">Test: {test.title}</p>

            <div>
                <InputLabel
                    htmlFor="body"
                    value="Problem statement"
                    className={labelClass}
                />
                <textarea
                    id="body"
                    className={`mt-1 block w-full ${fieldClass}`}
                    rows={7}
                    value={data.body}
                    onChange={(event) => setData('body', event.target.value)}
                    required
                />
                <InputError message={errors.body} className="mt-2" />
            </div>

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
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
                        className={`mt-1 block w-full ${fieldClass}`}
                        value={data.marks}
                        onChange={(event) =>
                            setData('marks', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.marks} className="mt-2" />
                </div>

                <div>
                    <InputLabel
                        htmlFor="order"
                        value="Sort order"
                        className={labelClass}
                    />
                    <TextInput
                        id="order"
                        type="number"
                        min="0"
                        className={`mt-1 block w-full ${fieldClass}`}
                        value={data.order}
                        onChange={(event) =>
                            setData('order', event.target.value)
                        }
                    />
                    <InputError message={errors.order} className="mt-2" />
                </div>

                <div
                    className="relative"
                    onBlur={(event) => {
                        if (
                            event.relatedTarget instanceof Node &&
                            event.currentTarget.contains(event.relatedTarget)
                        ) {
                            return;
                        }

                        setIsDifficultyOpen(false);
                    }}
                >
                    <InputLabel
                        htmlFor="difficulty"
                        value="Difficulty"
                        className={labelClass}
                    />
                    <button
                        id="difficulty"
                        type="button"
                        className={`mt-1 flex w-full items-center justify-between border px-3 py-2 text-left ${fieldClass}`}
                        aria-haspopup="listbox"
                        aria-expanded={isDifficultyOpen}
                        onClick={() =>
                            setIsDifficultyOpen((isOpen) => !isOpen)
                        }
                    >
                        <span>{selectedDifficulty}</span>
                        <span className="text-emerald-400" aria-hidden="true">
                            v
                        </span>
                    </button>
                    {isDifficultyOpen && (
                        <div
                            className="absolute z-20 mt-2 w-full overflow-hidden rounded-xl border border-zinc-800 bg-zinc-950 shadow-2xl shadow-black/40"
                            role="listbox"
                            aria-label="Difficulty"
                        >
                            {difficulties.map((difficulty) => {
                                const selected =
                                    difficulty.value === data.difficulty;

                                return (
                                    <button
                                        key={difficulty.value}
                                        type="button"
                                        role="option"
                                        aria-selected={selected}
                                        className={`flex w-full items-center justify-between px-3 py-2 text-left text-sm font-semibold transition ${
                                            selected
                                                ? 'bg-emerald-500 text-black'
                                                : 'text-zinc-300 hover:bg-emerald-500/10 hover:text-emerald-200'
                                        }`}
                                        onClick={() =>
                                            selectDifficulty(difficulty.value)
                                        }
                                    >
                                        {difficulty.label}
                                        {selected && (
                                            <span
                                                className="h-1.5 w-1.5 rounded-full bg-black"
                                                aria-hidden="true"
                                            />
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    )}
                    <InputError message={errors.difficulty} className="mt-2" />
                </div>

                <div>
                    <InputLabel
                        htmlFor="time_limit_minutes"
                        value="Time limit (minutes)"
                        className={labelClass}
                    />
                    <TextInput
                        id="time_limit_minutes"
                        type="number"
                        min="0.0083"
                        max="60"
                        step="0.0001"
                        className={`mt-1 block w-full ${fieldClass}`}
                        value={data.time_limit_minutes}
                        onChange={(event) =>
                            setData('time_limit_minutes', event.target.value)
                        }
                        required
                    />
                    <p className="mt-1 text-xs text-zinc-500">
                        Use minutes from 0.0083 up to 60. Decimal values are
                        allowed, for example 0.5 for 30 seconds.
                    </p>
                    <InputError
                        message={
                            formErrors.time_limit_minutes ??
                            formErrors.time_limit_ms
                        }
                        className="mt-2"
                    />
                </div>
            </div>

            <div>
                <InputLabel
                    value="Supported languages"
                    className={labelClass}
                />
                <div className="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    {languages.map((language) => {
                        const selected = data.supported_languages.includes(
                            language.value,
                        );

                        return (
                            <label
                                key={language.value}
                                className="flex items-center gap-2 rounded-xl border border-zinc-800 bg-zinc-950/70 px-3 py-2 text-sm font-medium text-zinc-300 transition hover:border-zinc-700 hover:text-white"
                            >
                                <input
                                    type="checkbox"
                                    className="sr-only"
                                    checked={selected}
                                    onChange={() =>
                                        toggleLanguage(language.value)
                                    }
                                />
                                <span
                                    aria-hidden="true"
                                    className={`flex h-4 w-4 items-center justify-center rounded border transition ${
                                        selected
                                            ? 'border-emerald-400 bg-emerald-500 text-black'
                                            : 'border-zinc-500 bg-zinc-950'
                                    }`}
                                >
                                    {selected && (
                                        <span className="h-1.5 w-1.5 rounded-sm bg-black" />
                                    )}
                                </span>
                                {language.label}
                            </label>
                        );
                    })}
                </div>
                <InputError
                    message={formErrors.supported_languages}
                    className="mt-2"
                />
            </div>

            {selectedLanguages.length > 0 && (
                <div className="space-y-4">
                    <InputLabel value="Starter code" className={labelClass} />
                    {selectedLanguages.map((language) => (
                        <div key={language.value}>
                            <InputLabel
                                htmlFor={`starter_code_${language.value}`}
                                value={language.label}
                                className={labelClass}
                            />
                            <textarea
                                id={`starter_code_${language.value}`}
                                className={`mt-1 block w-full ${codeFieldClass}`}
                                rows={7}
                                value={data.starter_code[language.value] ?? ''}
                                onChange={(event) =>
                                    updateStarterCode(
                                        language.value,
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={
                                    formErrors[
                                        `starter_code.${language.value}`
                                    ]
                                }
                                className="mt-2"
                            />
                        </div>
                    ))}
                </div>
            )}

            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <InputLabel value="Test cases" className={labelClass} />
                    <button
                        type="button"
                        onClick={addTestCase}
                        className="text-sm font-semibold text-emerald-300 underline-offset-4 transition hover:text-emerald-200 hover:underline"
                    >
                        Add test case
                    </button>
                </div>
                <InputError message={formErrors.test_cases} className="mt-2" />

                {data.test_cases.map((testCase, index) => (
                    <div
                        key={index}
                        className="space-y-4 rounded-2xl border border-zinc-800 bg-zinc-950/70 p-4"
                    >
                        <div className="flex items-center justify-between gap-4">
                            <p className="text-sm font-semibold text-white">
                                Test case {index + 1}
                            </p>
                            <button
                                type="button"
                                onClick={() => removeTestCase(index)}
                                className="text-sm font-semibold text-red-300 underline-offset-4 transition hover:text-red-200 hover:underline disabled:text-zinc-600"
                                disabled={data.test_cases.length <= 1}
                            >
                                Remove
                            </button>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <InputLabel
                                    htmlFor={`test_cases_${index}_input`}
                                    value="Input"
                                    className={labelClass}
                                />
                                <textarea
                                    id={`test_cases_${index}_input`}
                                    className={`mt-1 block w-full ${codeFieldClass}`}
                                    rows={4}
                                    value={testCase.input}
                                    onChange={(event) =>
                                        updateTestCase(
                                            index,
                                            'input',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={
                                        formErrors[
                                            `test_cases.${index}.input`
                                        ]
                                    }
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    htmlFor={`test_cases_${index}_expected_output`}
                                    value="Expected output"
                                    className={labelClass}
                                />
                                <textarea
                                    id={`test_cases_${index}_expected_output`}
                                    className={`mt-1 block w-full ${codeFieldClass}`}
                                    rows={4}
                                    value={testCase.expected_output}
                                    onChange={(event) =>
                                        updateTestCase(
                                            index,
                                            'expected_output',
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={
                                        formErrors[
                                            `test_cases.${index}.expected_output`
                                        ]
                                    }
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-[auto_12rem]">
                            <label className="flex items-center gap-2 text-sm font-medium text-zinc-300">
                                <input
                                    type="checkbox"
                                    className="sr-only"
                                    checked={testCase.is_hidden}
                                    onChange={(event) =>
                                        updateTestCase(
                                            index,
                                            'is_hidden',
                                            event.target.checked,
                                        )
                                    }
                                />
                                <span
                                    aria-hidden="true"
                                    className={`flex h-4 w-4 items-center justify-center rounded border transition ${
                                        testCase.is_hidden
                                            ? 'border-emerald-400 bg-emerald-500 text-black'
                                            : 'border-zinc-500 bg-zinc-950'
                                    }`}
                                >
                                    {testCase.is_hidden && (
                                        <span className="h-1.5 w-1.5 rounded-sm bg-black" />
                                    )}
                                </span>
                                Hidden test case
                            </label>

                            <div>
                                <InputLabel
                                    htmlFor={`test_cases_${index}_points`}
                                    value="Points"
                                    className={labelClass}
                                />
                                <TextInput
                                    id={`test_cases_${index}_points`}
                                    type="number"
                                    min="1"
                                    className={`mt-1 block w-full ${fieldClass}`}
                                    value={testCase.points}
                                    onChange={(event) =>
                                        updateTestCase(
                                            index,
                                            'points',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={
                                        formErrors[
                                            `test_cases.${index}.points`
                                        ]
                                    }
                                    className="mt-2"
                                />
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="flex flex-wrap items-center gap-3">
                <PrimaryButton
                    disabled={processing}
                    className={primaryButtonClass}
                >
                    {submitLabel}
                </PrimaryButton>
                <Link
                    href={route('admin.tests.questions.index', test.id)}
                    className={secondaryButtonClass}
                >
                    Cancel
                </Link>
            </div>
        </form>
    );
}

function minutesToMilliseconds(value: string): number {
    const parsed = Number(value);

    if (Number.isNaN(parsed)) {
        return 0;
    }

    return Math.round(parsed * 60_000);
}
