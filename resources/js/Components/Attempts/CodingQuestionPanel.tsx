import InputError from '@/Components/InputError';
import SecondaryButton from '@/Components/SecondaryButton';
import axios from 'axios';
import { useCallback, useEffect, useMemo, useState } from 'react';
import MonacoCodeEditor from './MonacoCodeEditor';

export type CodingQuestionDraft = {
    language: string | null;
    submitted_code: string | null;
};

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
    saved_answer: CodingQuestionDraft | null;
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
    draftAnswer?: CodingQuestionDraft | null;
    onDraftChange?: (questionId: number, draft: CodingQuestionDraft) => void;
    submitError?: string;
};

const secondaryActionClassName =
    '!rounded-xl !border-zinc-700 !bg-zinc-950 !px-5 !py-2.5 !text-zinc-100 hover:!border-emerald-400 hover:!text-emerald-300 focus:!ring-emerald-500 focus:!ring-offset-zinc-950 disabled:!opacity-50';

const selectInputClassName =
    'mt-1 block w-full !rounded-xl !border-zinc-700 !bg-zinc-950 !text-zinc-100 !shadow-none outline-none transition focus:!border-emerald-500 focus:!ring-2 focus:!ring-emerald-500/30 sm:w-64';

export default function CodingQuestionPanel({
    question,
    questionNumber,
    saveUrl,
    runUrl,
    disabled,
    draftAnswer,
    onDraftChange,
    submitError,
}: Props) {
    const initialLanguage = useMemo(
        () => initialSelectedLanguage(question, draftAnswer),
        [draftAnswer, question],
    );
    const [selectedLanguage, setSelectedLanguage] = useState(initialLanguage);
    const [codeByLanguage, setCodeByLanguage] = useState(() =>
        initialCodeByLanguage(question, initialLanguage, draftAnswer),
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
        setRunResult(null);
        onDraftChange?.(question.id, {
            language,
            submitted_code: nextCode,
        });
        void saveAnswer(language, nextCode);
    };

    const updateCode = (value: string) => {
        setCodeByLanguage({
            ...codeByLanguage,
            [selectedLanguage]: value,
        });
        setSaveStatus('unsaved');
        onDraftChange?.(question.id, {
            language: selectedLanguage,
            submitted_code: value,
        });
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
        <div className="overflow-hidden rounded-[18px] border border-zinc-800 bg-zinc-900 shadow-2xl shadow-black/20">
            <div className="border-b border-zinc-800 px-6 py-5">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p className="text-sm font-medium uppercase tracking-[0.28em] text-emerald-300">
                            Coding question
                        </p>
                        <h3 className="mt-2 text-lg font-semibold text-white">
                            Question {questionNumber}
                        </h3>
                        <p className="mt-2 text-sm text-zinc-500">
                            {question.marks} marks
                            {question.difficulty
                                ? ` - ${formatLabel(question.difficulty)}`
                                : ''}
                        </p>
                    </div>
                    <div className="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-sm font-medium text-emerald-200">
                        {statusLabel(saveStatus)}
                    </div>
                </div>
            </div>

            <div className="grid lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:divide-x lg:divide-zinc-800">
                <section className="space-y-6 p-6">
                    <div>
                        <h4 className="text-sm font-semibold uppercase tracking-wide text-zinc-500">
                            Problem
                        </h4>
                        <p className="mt-3 whitespace-pre-line text-sm leading-7 text-zinc-200">
                            {question.body}
                        </p>
                    </div>

                    <div>
                        <h4 className="text-sm font-semibold uppercase tracking-wide text-zinc-500">
                            Visible test cases
                        </h4>
                        <div className="mt-4 space-y-3">
                            {question.visible_test_cases.map((testCase, index) => (
                                <div
                                    key={testCase.id}
                                    className="rounded-xl border border-zinc-800 bg-zinc-950/70 p-4"
                                >
                                    <p className="text-xs font-medium uppercase tracking-wide text-zinc-500">
                                        Test case {index + 1}
                                    </p>
                                    <div className="mt-3 grid gap-3 md:grid-cols-2">
                                        <OutputBlock
                                            label="Input"
                                            value={testCase.input}
                                        />
                                        <OutputBlock
                                            label="Expected Output"
                                            value={testCase.expected_output}
                                        />
                                    </div>
                                </div>
                            ))}

                            {question.visible_test_cases.length === 0 && (
                                <p className="text-sm text-zinc-500">
                                    No visible test cases provided.
                                </p>
                            )}
                        </div>
                    </div>
                </section>

                <section className="flex min-h-full flex-col">
                    <div className="flex flex-wrap items-end justify-between gap-4 border-b border-zinc-800 px-6 py-5">
                        <div>
                            <label
                                htmlFor={`coding_language_${question.id}`}
                                className="text-sm font-medium text-zinc-300"
                            >
                                Language
                            </label>
                            <select
                                id={`coding_language_${question.id}`}
                                className={selectInputClassName}
                                value={selectedLanguage}
                                onChange={(event) =>
                                    changeLanguage(event.target.value)
                                }
                                disabled={disabled}
                            >
                                {question.supported_languages.map((language) => (
                                    <option key={language} value={language}>
                                        {languageLabel(language)}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="flex flex-wrap justify-end gap-3">
                            <SecondaryButton
                                type="button"
                                className={secondaryActionClassName}
                                onClick={() =>
                                    void saveAnswer(selectedLanguage, currentCode)
                                }
                                disabled={disabled || saveStatus === 'saving'}
                            >
                                Save code
                            </SecondaryButton>
                            <SecondaryButton
                                type="button"
                                className={secondaryActionClassName}
                                onClick={runCode}
                                disabled={
                                    disabled ||
                                    running ||
                                    saveStatus === 'saving'
                                }
                            >
                                {running ? 'Running...' : 'Run Code'}
                            </SecondaryButton>
                        </div>
                    </div>

                    <div className="border-b border-zinc-800 bg-zinc-950">
                        <MonacoCodeEditor
                            language={selectedLanguage}
                            value={currentCode}
                            onChange={updateCode}
                            disabled={disabled}
                        />
                    </div>

                    <div className="space-y-3 px-6 py-5">
                        {errorMessage && (
                            <p className="text-sm font-medium text-red-300">
                                {errorMessage}
                            </p>
                        )}

                        <InputError message={submitError} className="mt-0" />

                        {runResult && <RunResultPanel run={runResult} />}
                    </div>
                </section>
            </div>
        </div>
    );
}

function RunResultPanel({ run }: { run: RunResult }) {
    return (
        <div className="rounded-xl border border-zinc-800 bg-zinc-950/70 p-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h4 className="text-sm font-semibold text-white">
                    Run results
                </h4>
                <p className="text-sm text-zinc-400">
                    {run.summary.passed}/{run.summary.total} passed
                </p>
            </div>

            <div className="mt-4 space-y-3">
                {run.results.map((result, index) => (
                    <div
                        key={result.question_test_case_id}
                        className="rounded-xl border border-zinc-800 bg-zinc-900 p-4"
                    >
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <p className="text-sm font-semibold text-white">
                                Test Case #{index + 1}
                            </p>
                            <span
                                className={
                                    'rounded-full px-2.5 py-1 text-xs font-medium ' +
                                    (result.passed
                                        ? 'border border-emerald-400/20 bg-emerald-400/10 text-emerald-200'
                                        : 'border border-red-400/20 bg-red-400/10 text-red-200')
                                }
                            >
                                {result.passed ? 'Passed' : 'Failed'}
                            </span>
                        </div>
                        <p className="mt-1 text-xs text-zinc-500">
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
                                <OutputBlock
                                    label="STDOUT"
                                    value={result.stdout}
                                    emptyText="No standard output"
                                />
                                <OutputBlock
                                    label="STDERR"
                                    value={result.stderr}
                                    emptyText="No error output"
                                />
                                <OutputBlock
                                    label="Compile Output"
                                    value={result.compile_output}
                                    emptyText="No compile output"
                                />
                                <OutputBlock
                                    label="Message"
                                    value={result.message}
                                    emptyText="No additional message"
                                />
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}

function OutputBlock({
    label,
    value,
    emptyText = '',
}: {
    label: string;
    value: string | null;
    emptyText?: string;
}) {
    const displayValue =
        value === null || value.trim() === '' ? emptyText : value;

    return (
        <div>
            <p className="text-xs font-medium uppercase tracking-wide text-zinc-500">
                {label}
            </p>
            <pre
                className={
                    'mt-1 min-h-16 whitespace-pre-wrap rounded-xl border border-zinc-800 bg-zinc-950 p-3 text-xs ' +
                    (displayValue === emptyText && emptyText !== ''
                        ? 'text-zinc-600'
                        : 'text-zinc-200')
                }
            >
                {displayValue}
            </pre>
        </div>
    );
}

function initialSelectedLanguage(
    question: CodingQuestion,
    draftAnswer?: CodingQuestionDraft | null,
): string {
    const preferredLanguage = draftAnswer?.language ?? question.saved_answer?.language;

    if (
        preferredLanguage &&
        question.supported_languages.includes(preferredLanguage)
    ) {
        return preferredLanguage;
    }

    return question.supported_languages[0] ?? 'javascript';
}

function initialCodeByLanguage(
    question: CodingQuestion,
    selectedLanguage: string,
    draftAnswer?: CodingQuestionDraft | null,
): Record<string, string> {
    const code = Object.fromEntries(
        question.supported_languages.map((language) => [
            language,
            question.starter_code[language] ?? '',
        ]),
    );
    const preferredAnswer = draftAnswer ?? question.saved_answer;

    if (preferredAnswer?.language) {
        code[preferredAnswer.language] = preferredAnswer.submitted_code ?? '';
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
