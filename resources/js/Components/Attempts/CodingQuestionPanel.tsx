import InputError from '@/Components/InputError';
import SecondaryButton from '@/Components/SecondaryButton';
import axios from 'axios';
import { useCallback, useEffect, useMemo, useState } from 'react';
import MonacoCodeEditor from './MonacoCodeEditor';

export type CodingQuestion = {
    id: number;
    type: 'coding';
    body: string;
    marks: number;
    difficulty: string | null;
    supported_languages: string[];
    starter_code: Record<string, string | null>;
    visible_test_cases: {
        id: number;
        input: string | null;
        expected_output: string;
    }[];
    saved_answer: {
        language: string | null;
        submitted_code: string | null;
    } | null;
};

type SaveStatus = 'saved' | 'saving' | 'unsaved' | 'failed';

type RunResult = {
    id: number;
    status: string;
    summary: {
        status: string;
        message: string | null;
        total: number;
        passed: number;
        failed: number;
    };
    results: TestCaseRunResult[];
};

type TestCaseRunResult = {
    question_test_case_id: number;
    status: string;
    passed: boolean;
    input: string | null;
    expected_output: string | null;
    actual_output: string | null;
    stdout: string | null;
    stderr: string | null;
    compile_output: string | null;
    message: string | null;
    time: string | null;
    memory: number | null;
    judge0_status_description: string | null;
};

type Props = {
    question: CodingQuestion;
    questionNumber: number;
    saveUrl: string;
    runUrl: string;
    disabled: boolean;
    registerSave?: (questionId: number, saveHandler: () => Promise<void>) => void;
    submitError?: string;
};

export default function CodingQuestionPanel({
    question,
    questionNumber,
    saveUrl,
    runUrl,
    disabled,
    registerSave,
    submitError,
}: Props) {
    const initialLanguage = useMemo(
        () => initialSelectedLanguage(question),
        [question],
    );
    const [selectedLanguage, setSelectedLanguage] = useState(initialLanguage);
    const [codeByLanguage, setCodeByLanguage] = useState(() =>
        initialCodeByLanguage(question, initialLanguage),
    );
    const [saveStatus, setSaveStatus] = useState<SaveStatus>('saved');
    const [running, setRunning] = useState(false);
    const [runResult, setRunResult] = useState<RunResult | null>(null);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    const currentCode =
        codeByLanguage[selectedLanguage] ??
        question.starter_code[selectedLanguage] ??
        '';

    const saveAnswer = useCallback(
        async (language: string, submittedCode: string): Promise<boolean> => {
            if (disabled) {
                return false;
            }

            setSaveStatus('saving');
            setErrorMessage(null);

            try {
                await axios.post(saveUrl, {
                    question_id: question.id,
                    language,
                    submitted_code: submittedCode,
                });
                setSaveStatus('saved');
                return true;
            } catch (error) {
                setSaveStatus('failed');
                setErrorMessage(validationMessage(error));
                return false;
            }
        },
        [disabled, question.id, saveUrl],
    );

    useEffect(() => {
        if (disabled || saveStatus !== 'unsaved') {
            return;
        }

        const timeoutId = window.setTimeout(() => {
            void saveAnswer(selectedLanguage, currentCode);
        }, 4000);

        return () => window.clearTimeout(timeoutId);
    }, [currentCode, disabled, saveAnswer, saveStatus, selectedLanguage]);

    useEffect(() => {
        registerSave?.(question.id, async () => {
            await saveAnswer(selectedLanguage, currentCode);
        });
    }, [currentCode, question.id, registerSave, saveAnswer, selectedLanguage]);

    const changeLanguage = (language: string) => {
        if (language === selectedLanguage) {
            return;
        }

        const nextCode =
            codeByLanguage[language] ?? question.starter_code[language] ?? '';

        setCodeByLanguage({
            ...codeByLanguage,
            [selectedLanguage]: currentCode,
            [language]: nextCode,
        });
        setSelectedLanguage(language);
        setSaveStatus('unsaved');
        void saveAnswer(language, nextCode);
    };

    const updateCode = (value: string) => {
        setCodeByLanguage({
            ...codeByLanguage,
            [selectedLanguage]: value,
        });
        setSaveStatus('unsaved');
    };

    const runCode = async () => {
        if (disabled || running) {
            return;
        }

        setRunning(true);
        setRunResult(null);
        setErrorMessage(null);

        const saved = await saveAnswer(selectedLanguage, currentCode);

        if (!saved) {
            setRunning(false);
            return;
        }

        try {
            const response = await axios.post<{ run: RunResult }>(runUrl, {
                question_id: question.id,
                language: selectedLanguage,
                submitted_code: currentCode,
            });

            setRunResult(response.data.run);
        } catch (error) {
            setErrorMessage(validationMessage(error));
        } finally {
            setRunning(false);
        }
    };

    return (
        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h3 className="text-base font-semibold text-gray-900">
                        Question {questionNumber}
                    </h3>
                    <p className="mt-1 text-sm text-gray-500">
                        Coding - {question.marks} marks
                        {question.difficulty
                            ? ` - ${formatLabel(question.difficulty)}`
                            : ''}
                    </p>
                </div>
                <div className="text-sm font-medium text-gray-600">
                    {statusLabel(saveStatus)}
                </div>
            </div>

            <p className="mt-4 whitespace-pre-line text-sm text-gray-800">
                {question.body}
            </p>

            <div className="mt-5">
                <label
                    htmlFor={`coding_language_${question.id}`}
                    className="text-sm font-medium text-gray-700"
                >
                    Language
                </label>
                <select
                    id={`coding_language_${question.id}`}
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-64"
                    value={selectedLanguage}
                    onChange={(event) => changeLanguage(event.target.value)}
                    disabled={disabled}
                >
                    {question.supported_languages.map((language) => (
                        <option key={language} value={language}>
                            {languageLabel(language)}
                        </option>
                    ))}
                </select>
            </div>

            <div className="mt-5 overflow-hidden rounded-md border border-gray-200">
                <MonacoCodeEditor
                    language={selectedLanguage}
                    value={currentCode}
                    onChange={updateCode}
                    disabled={disabled}
                />
            </div>

            {errorMessage && (
                <p className="mt-3 text-sm font-medium text-red-600">
                    {errorMessage}
                </p>
            )}

            <InputError message={submitError} className="mt-3" />

            <div className="mt-4 flex flex-wrap justify-end gap-3">
                <SecondaryButton
                    type="button"
                    onClick={runCode}
                    disabled={disabled || running || saveStatus === 'saving'}
                >
                    {running ? 'Running...' : 'Run Code'}
                </SecondaryButton>
                <SecondaryButton
                    type="button"
                    onClick={() => saveAnswer(selectedLanguage, currentCode)}
                    disabled={disabled || saveStatus === 'saving'}
                >
                    Save code
                </SecondaryButton>
            </div>

            {runResult && <RunResultPanel run={runResult} />}

            <div className="mt-6">
                <h4 className="text-sm font-semibold text-gray-900">
                    Visible test cases
                </h4>
                <div className="mt-3 space-y-3">
                    {question.visible_test_cases.map((testCase, index) => (
                        <div
                            key={testCase.id}
                            className="rounded-md border border-gray-200 bg-gray-50 p-4"
                        >
                            <p className="text-xs font-medium uppercase text-gray-500">
                                Test case {index + 1}
                            </p>
                            <div className="mt-3 grid gap-3 md:grid-cols-2">
                                <OutputBlock label="Input" value={testCase.input} />
                                <OutputBlock
                                    label="Expected Output"
                                    value={testCase.expected_output}
                                />
                            </div>
                        </div>
                    ))}

                    {question.visible_test_cases.length === 0 && (
                        <p className="text-sm text-gray-600">
                            No visible test cases provided.
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}

function RunResultPanel({ run }: { run: RunResult }) {
    return (
        <div className="mt-6 rounded-md border border-gray-200 bg-gray-50 p-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h4 className="text-sm font-semibold text-gray-900">
                    Run results
                </h4>
                <p className="text-sm text-gray-600">
                    {run.summary.passed}/{run.summary.total} passed
                </p>
            </div>

            <div className="mt-4 space-y-3">
                {run.results.map((result, index) => (
                    <div
                        key={result.question_test_case_id}
                        className="rounded-md border border-gray-200 bg-white p-4"
                    >
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <p className="text-sm font-semibold text-gray-900">
                                Test Case #{index + 1}
                            </p>
                            <span
                                className={
                                    'rounded-full px-2.5 py-1 text-xs font-medium ' +
                                    (result.passed
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-red-100 text-red-700')
                                }
                            >
                                {result.passed ? 'Passed' : 'Failed'}
                            </span>
                        </div>
                        <p className="mt-1 text-xs text-gray-500">
                            Status:{' '}
                            {result.judge0_status_description ??
                                formatLabel(result.status)}
                        </p>

                        <div className="mt-3 grid gap-3 md:grid-cols-3">
                            <OutputBlock label="Input" value={result.input} />
                            <OutputBlock
                                label="Expected Output"
                                value={result.expected_output}
                            />
                            <OutputBlock
                                label="Actual Output"
                                value={result.actual_output}
                            />
                        </div>

                        {(result.stdout ||
                            result.stderr ||
                            result.compile_output ||
                            result.message) && (
                            <div className="mt-3 grid gap-3 md:grid-cols-2">
                                <OutputBlock label="STDOUT" value={result.stdout} />
                                <OutputBlock label="STDERR" value={result.stderr} />
                                <OutputBlock
                                    label="Compile Output"
                                    value={result.compile_output}
                                />
                                <OutputBlock
                                    label="Message"
                                    value={result.message}
                                />
                            </div>
                        )}

                    </div>
                ))}
            </div>
        </div>
    );
}

function OutputBlock({ label, value }: { label: string; value: string | null }) {
    return (
        <div>
            <p className="text-xs font-medium uppercase text-gray-500">
                {label}
            </p>
            <pre className="mt-1 min-h-16 whitespace-pre-wrap rounded-md bg-white p-3 text-xs text-gray-800">
                {value ?? ''}
            </pre>
        </div>
    );
}

function initialSelectedLanguage(question: CodingQuestion): string {
    const savedLanguage = question.saved_answer?.language;

    if (
        savedLanguage &&
        question.supported_languages.includes(savedLanguage)
    ) {
        return savedLanguage;
    }

    return question.supported_languages[0] ?? 'javascript';
}

function initialCodeByLanguage(
    question: CodingQuestion,
    selectedLanguage: string,
): Record<string, string> {
    const code = Object.fromEntries(
        question.supported_languages.map((language) => [
            language,
            question.starter_code[language] ?? '',
        ]),
    );

    if (question.saved_answer?.language) {
        code[question.saved_answer.language] =
            question.saved_answer.submitted_code ?? '';
    }

    if (!code[selectedLanguage]) {
        code[selectedLanguage] = question.starter_code[selectedLanguage] ?? '';
    }

    return code;
}

function statusLabel(status: SaveStatus): string {
    return {
        saved: 'Saved',
        saving: 'Saving...',
        unsaved: 'Unsaved changes',
        failed: 'Failed to save',
    }[status];
}

function languageLabel(language: string): string {
    if (language === 'cpp') {
        return 'C++';
    }

    if (language === 'php') {
        return 'PHP';
    }

    return formatLabel(language);
}

function formatLabel(value: string): string {
    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function validationMessage(error: unknown): string {
    if (axios.isAxiosError(error) && error.response?.data?.errors) {
        const errors = error.response.data.errors as Record<string, string[]>;
        const firstError = Object.values(errors)[0]?.[0];

        if (firstError) {
            return firstError;
        }
    }

    if (
        axios.isAxiosError(error) &&
        typeof error.response?.data?.message === 'string'
    ) {
        return error.response.data.message;
    }

    return 'Unable to run or save this coding answer right now.';
}
