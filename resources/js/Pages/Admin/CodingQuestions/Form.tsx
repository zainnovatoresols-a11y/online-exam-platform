import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

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

    return (
        <form
            onSubmit={submit}
            className="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg"
        >
            <p className="text-sm text-gray-600">Test: {test.title}</p>

            <div>
                <InputLabel htmlFor="body" value="Problem statement" />
                <textarea
                    id="body"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    rows={7}
                    value={data.body}
                    onChange={(event) => setData('body', event.target.value)}
                    required
                />
                <InputError message={errors.body} className="mt-2" />
            </div>

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <InputLabel htmlFor="marks" value="Marks" />
                    <TextInput
                        id="marks"
                        type="number"
                        min="1"
                        className="mt-1 block w-full"
                        value={data.marks}
                        onChange={(event) =>
                            setData('marks', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.marks} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="order" value="Sort order" />
                    <TextInput
                        id="order"
                        type="number"
                        min="0"
                        className="mt-1 block w-full"
                        value={data.order}
                        onChange={(event) =>
                            setData('order', event.target.value)
                        }
                    />
                    <InputError message={errors.order} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="difficulty" value="Difficulty" />
                    <select
                        id="difficulty"
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        value={data.difficulty}
                        onChange={(event) =>
                            setData('difficulty', event.target.value)
                        }
                        required
                    >
                        {difficulties.map((difficulty) => (
                            <option
                                key={difficulty.value}
                                value={difficulty.value}
                            >
                                {difficulty.label}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.difficulty} className="mt-2" />
                </div>

                <div>
                    <InputLabel
                        htmlFor="time_limit_minutes"
                        value="Time limit (minutes)"
                    />
                    <TextInput
                        id="time_limit_minutes"
                        type="number"
                        min="0.0083"
                        max="60"
                        step="0.0001"
                        className="mt-1 block w-full"
                        value={data.time_limit_minutes}
                        onChange={(event) =>
                            setData('time_limit_minutes', event.target.value)
                        }
                        required
                    />
                    <p className="mt-1 text-xs text-gray-500">
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
                <InputLabel value="Supported languages" />
                <div className="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    {languages.map((language) => (
                        <label
                            key={language.value}
                            className="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700"
                        >
                            <input
                                type="checkbox"
                                checked={data.supported_languages.includes(
                                    language.value,
                                )}
                                onChange={() => toggleLanguage(language.value)}
                            />
                            {language.label}
                        </label>
                    ))}
                </div>
                <InputError
                    message={formErrors.supported_languages}
                    className="mt-2"
                />
            </div>

            {selectedLanguages.length > 0 && (
                <div className="space-y-4">
                    <InputLabel value="Starter code" />
                    {selectedLanguages.map((language) => (
                        <div key={language.value}>
                            <InputLabel
                                htmlFor={`starter_code_${language.value}`}
                                value={language.label}
                            />
                            <textarea
                                id={`starter_code_${language.value}`}
                                className="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                    <InputLabel value="Test cases" />
                    <button
                        type="button"
                        onClick={addTestCase}
                        className="text-sm font-medium text-gray-900 underline"
                    >
                        Add test case
                    </button>
                </div>
                <InputError message={formErrors.test_cases} className="mt-2" />

                {data.test_cases.map((testCase, index) => (
                    <div
                        key={index}
                        className="space-y-4 rounded-md border border-gray-200 p-4"
                    >
                        <div className="flex items-center justify-between gap-4">
                            <p className="text-sm font-semibold text-gray-900">
                                Test case {index + 1}
                            </p>
                            <button
                                type="button"
                                onClick={() => removeTestCase(index)}
                                className="text-sm text-red-600 underline disabled:text-gray-400"
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
                                />
                                <textarea
                                    id={`test_cases_${index}_input`}
                                    className="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                />
                                <textarea
                                    id={`test_cases_${index}_expected_output`}
                                    className="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                            <label className="flex items-center gap-2 text-sm text-gray-700">
                                <input
                                    type="checkbox"
                                    checked={testCase.is_hidden}
                                    onChange={(event) =>
                                        updateTestCase(
                                            index,
                                            'is_hidden',
                                            event.target.checked,
                                        )
                                    }
                                />
                                Hidden test case
                            </label>

                            <div>
                                <InputLabel
                                    htmlFor={`test_cases_${index}_points`}
                                    value="Points"
                                />
                                <TextInput
                                    id={`test_cases_${index}_points`}
                                    type="number"
                                    min="1"
                                    className="mt-1 block w-full"
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

            <div className="flex items-center gap-4">
                <PrimaryButton disabled={processing}>{submitLabel}</PrimaryButton>
                <Link
                    href={route('admin.tests.questions.index', test.id)}
                    className="text-sm text-gray-600 underline"
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
