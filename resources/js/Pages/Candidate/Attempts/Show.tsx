import InputError from '@/Components/InputError';
import CodingQuestionPanel, {
    CodingQuestion,
} from '@/Components/Attempts/CodingQuestionPanel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { useProctoringEvents } from '@/features/proctoring/useProctoringEvents';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PublicAssessmentLayout from '@/Layouts/PublicAssessmentLayout';
import { Head, useForm } from '@inertiajs/react';
import {
    FormEventHandler,
    PropsWithChildren,
    ReactNode,
    useCallback,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';

type Attempt = {
    id: number;
    access_token?: string | null;
    is_public?: boolean;
    status: string;
    started_at: string | null;
    expires_at: string | null;
    server_now: string;
};

type Test = {
    id: number;
    title: string;
    duration_minutes: number;
    pass_mark: number;
    status: string;
    organization: { id: number; name: string } | null;
    creator: { id: number; name: string; email: string } | null;
};

type McqQuestion = {
    id: number;
    type: 'mcq';
    body: string;
    marks: number;
    options: {
        id: number;
        body: string;
    }[];
};

type Question = McqQuestion | CodingQuestion;

type AttemptForm = {
    answers: Record<string, number | string>;
};

export default function Show({
    attempt,
    test,
    questions,
    saved_answers,
}: {
    attempt: Attempt;
    test: Test;
    questions: Question[];
    saved_answers: Record<string, number>;
}) {
    const { data, setData, post, processing, errors } = useForm<AttemptForm>({
        answers: saved_answers,
    });

    const formErrors = errors as Record<string, string>;
    const codingSaveHandlers = useRef<Map<number, () => Promise<void>>>(
        new Map(),
    );
    const [submitting, setSubmitting] = useState(false);
    const [remainingSeconds, setRemainingSeconds] = useState(() =>
        secondsUntilExpiry(attempt.expires_at, attempt.server_now),
    );
    const expired = remainingSeconds <= 0;
    const timeRemaining = useMemo(
        () => formatRemainingTime(remainingSeconds),
        [remainingSeconds],
    );
    const { enterFullscreen, fullscreenActive, fullscreenSupported } =
        useProctoringEvents(
            attempt,
            expired || attempt.status !== 'in_progress',
    );

    useEffect(() => {
        const timer = window.setInterval(() => {
            setRemainingSeconds((seconds) => Math.max(seconds - 1, 0));
        }, 1000);

        return () => window.clearInterval(timer);
    }, []);

    const registerCodingSave = useCallback(
        (questionId: number, saveHandler: () => Promise<void>) => {
            codingSaveHandlers.current.set(questionId, saveHandler);
        },
        [],
    );

    const submit: FormEventHandler = async (event) => {
        event.preventDefault();

        setSubmitting(true);

        try {
            await Promise.all(
                Array.from(codingSaveHandlers.current.values()).map(
                    (saveHandler) => saveHandler(),
                ),
            );

            post(attemptRoute(attempt, 'submit'), {
                onFinish: () => setSubmitting(false),
            });
        } catch {
            setSubmitting(false);
        }
    };

    const save = () => {
        post(attemptRoute(attempt, 'save'), {
            preserveScroll: true,
        });
    };

    const selectAnswer = (questionId: number, optionId: number) => {
        setData('answers', {
            ...data.answers,
            [questionId]: optionId,
        });
    };

    return (
        <AssessmentLayout
            isPublic={Boolean(attempt.is_public)}
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    {test.title}
                </h2>
            }
        >
            <Head title={test.title} />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <p className="text-sm font-medium uppercase text-gray-500">
                            Assessment attempt
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold text-gray-900">
                            {test.title}
                        </h1>

                        <dl className="mt-6 grid gap-4 sm:grid-cols-3">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Duration
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {test.duration_minutes} minutes
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Pass percentage
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {test.pass_mark}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Questions
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {questions.length}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Time remaining
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {timeRemaining}
                                </dd>
                            </div>
                        </dl>
                        {expired && (
                            <p className="mt-4 rounded-md bg-red-50 p-3 text-sm font-medium text-red-700">
                                Time is over. Answers can no longer be saved or
                                submitted.
                            </p>
                        )}
                        {fullscreenSupported && (
                            <div className="mt-4 flex flex-wrap items-center gap-3 rounded-md border border-gray-200 bg-gray-50 p-3">
                                <SecondaryButton
                                    type="button"
                                    onClick={enterFullscreen}
                                    disabled={fullscreenActive || expired}
                                >
                                    {fullscreenActive
                                        ? 'Fullscreen active'
                                        : 'Enter fullscreen'}
                                </SecondaryButton>
                                <span className="text-sm font-medium text-gray-600">
                                    Proctoring active
                                </span>
                            </div>
                        )}
                    </div>

                    <form onSubmit={submit} className="space-y-6">
                        {questions.map((question, questionIndex) => (
                            <QuestionPanel
                                key={question.id}
                                attempt={attempt}
                                question={question}
                                questionNumber={questionIndex + 1}
                                selectedAnswer={data.answers[question.id]}
                                formErrors={formErrors}
                                expired={expired}
                                registerCodingSave={registerCodingSave}
                                onSelectAnswer={selectAnswer}
                            />
                        ))}

                        <div className="flex flex-wrap items-center justify-between gap-4 bg-white p-6 shadow-sm sm:rounded-lg">
                            <InputError
                                message={formErrors.attempt}
                                className="mt-0"
                            />
                            <div className="flex gap-3">
                                <SecondaryButton
                                    type="button"
                                    onClick={save}
                                    disabled={processing || submitting || expired}
                                >
                                    Save MCQ answers
                                </SecondaryButton>
                                <PrimaryButton
                                    disabled={processing || submitting || expired}
                                >
                                    Submit test
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AssessmentLayout>
    );
}

function QuestionPanel({
    attempt,
    question,
    questionNumber,
    selectedAnswer,
    formErrors,
    expired,
    registerCodingSave,
    onSelectAnswer,
}: {
    attempt: Attempt;
    question: Question;
    questionNumber: number;
    selectedAnswer: number | string | undefined;
    formErrors: Record<string, string>;
    expired: boolean;
    registerCodingSave: (questionId: number, saveHandler: () => Promise<void>) => void;
    onSelectAnswer: (questionId: number, optionId: number) => void;
}) {
    if (question.type === 'coding') {
        return (
            <CodingQuestionPanel
                question={question}
                questionNumber={questionNumber}
                saveUrl={codingAnswerRoute(attempt)}
                runUrl={codingRunRoute(attempt)}
                disabled={expired}
                registerSave={registerCodingSave}
            />
        );
    }

    return (
        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <h3 className="text-base font-semibold text-gray-900">
                    Question {questionNumber}
                </h3>
                <span className="text-sm text-gray-500">
                    {question.marks} marks
                </span>
            </div>

            <p className="mt-3 whitespace-pre-line text-sm text-gray-800">
                {question.body}
            </p>

            <div className="mt-5 space-y-3">
                {question.options.map((option) => (
                    <label
                        key={option.id}
                        className="flex cursor-pointer items-start gap-3 rounded-md border border-gray-200 p-3 text-sm text-gray-800"
                    >
                        <input
                            type="radio"
                            name={`answers.${question.id}`}
                            value={option.id}
                            checked={selectedAnswer === option.id}
                            onChange={() =>
                                onSelectAnswer(question.id, option.id)
                            }
                            className="mt-1"
                        />
                        <span>{option.body}</span>
                    </label>
                ))}
            </div>

            <InputError
                message={
                    formErrors[`answers.${question.id}`] ?? formErrors.answers
                }
                className="mt-3"
            />
        </div>
    );
}

function AssessmentLayout({
    isPublic,
    header,
    children,
}: PropsWithChildren<{ isPublic: boolean; header?: ReactNode }>) {
    if (isPublic) {
        return (
            <PublicAssessmentLayout header={header}>
                {children}
            </PublicAssessmentLayout>
        );
    }

    return <AuthenticatedLayout header={header}>{children}</AuthenticatedLayout>;
}

function attemptRoute(attempt: Attempt, action: 'save' | 'submit'): string {
    if (attempt.is_public && attempt.access_token) {
        return action === 'save'
            ? route('candidate.public-attempts.answers.save', attempt.access_token)
            : route('candidate.public-attempts.submit', attempt.access_token);
    }

    return action === 'save'
        ? route('candidate.attempts.answers.save', attempt.id)
        : route('candidate.attempts.submit', attempt.id);
}

function codingAnswerRoute(attempt: Attempt): string {
    if (attempt.is_public && attempt.access_token) {
        return route(
            'candidate.public-attempts.coding-answers.save',
            attempt.access_token,
        );
    }

    return route('candidate.attempts.coding-answers.save', attempt.id);
}

function codingRunRoute(attempt: Attempt): string {
    if (attempt.is_public && attempt.access_token) {
        return route(
            'candidate.public-attempts.coding-questions.run',
            attempt.access_token,
        );
    }

    return route('candidate.attempts.coding-questions.run', attempt.id);
}

function secondsUntilExpiry(
    expiresAt: string | null,
    serverNow: string,
): number {
    if (!expiresAt) {
        return 0;
    }

    const expiresAtTime = new Date(expiresAt).getTime();
    const serverNowTime = new Date(serverNow).getTime();

    return Math.max(Math.floor((expiresAtTime - serverNowTime) / 1000), 0);
}

function formatRemainingTime(totalSeconds: number): string {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    if (hours > 0) {
        return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }

    return `${minutes}:${String(seconds).padStart(2, '0')}`;
}
