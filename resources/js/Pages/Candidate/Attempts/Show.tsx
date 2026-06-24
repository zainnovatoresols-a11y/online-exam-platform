import InputError from '@/Components/InputError';
import CodingQuestionPanel, {
    CodingQuestion,
    CodingQuestionDraft,
} from '@/Components/Attempts/CodingQuestionPanel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { useProctoringEvents } from '@/features/proctoring/useProctoringEvents';
import {
    useProctoringGuards,
} from '@/features/proctoring/useProctoringGuards';
import {
    ProctoringRecordingControls,
    RecordingStatus,
    useProctoringRecordings,
} from '@/features/proctoring/useProctoringRecordings';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PublicAssessmentLayout from '@/Layouts/PublicAssessmentLayout';
import { Head, useForm } from '@inertiajs/react';
import axios from 'axios';
import {
    FormEventHandler,
    PropsWithChildren,
    ReactNode,
    useCallback,
    useEffect,
    useMemo,
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
    const orderedQuestions = useMemo(
        () =>
            [...questions].sort(
                (left, right) =>
                    questionTypePriority(left.type) -
                    questionTypePriority(right.type),
            ),
        [questions],
    );
    const { data, setData, post, processing, errors, clearErrors } = useForm<AttemptForm>({
        answers: saved_answers,
    });

    const formErrors = errors as Record<string, string>;
    const [codingDrafts, setCodingDrafts] = useState<
        Record<number, CodingQuestionDraft>
    >({});
    const [currentQuestionIndex, setCurrentQuestionIndex] = useState(() =>
        findInitialQuestionIndex(orderedQuestions, saved_answers),
    );
    const [submitting, setSubmitting] = useState(false);
    const [navigating, setNavigating] = useState(false);
    const [navigationError, setNavigationError] = useState<string | null>(null);
    const [remainingSeconds, setRemainingSeconds] = useState(() =>
        secondsUntilExpiry(attempt.expires_at, attempt.server_now),
    );
    const expired = remainingSeconds <= 0;
    const timeRemaining = useMemo(
        () => formatRemainingTime(remainingSeconds),
        [remainingSeconds],
    );
    const proctoringDisabled = expired || attempt.status !== 'in_progress';
    const recordings = useProctoringRecordings(attempt, proctoringDisabled);
    const recordingPermissionRequestActive =
        recordings.cameraStatus === 'requesting' ||
        recordings.screenStatus === 'requesting';
    const { enterFullscreen, fullscreenActive, fullscreenSupported } =
        useProctoringEvents(attempt, proctoringDisabled);
    const {
        acknowledgeViolation,
        latestBlockedAction,
        violation,
    } = useProctoringGuards(
        attempt,
        proctoringDisabled || recordingPermissionRequestActive,
    );
    const acknowledgeAndReturnToFullscreen = useCallback(async () => {
        if (fullscreenSupported && !fullscreenActive) {
            const enteredFullscreen = await enterFullscreen();

            if (!enteredFullscreen) {
                return;
            }
        }

        acknowledgeViolation();
    }, [
        acknowledgeViolation,
        enterFullscreen,
        fullscreenActive,
        fullscreenSupported,
    ]);
    const startScreenAndReturnToFullscreen = useCallback(async () => {
        if (fullscreenSupported && !document.fullscreenElement) {
            await enterFullscreen();
        }

        await recordings.startScreen();

        if (fullscreenSupported && !document.fullscreenElement) {
            void enterFullscreen();
        }
    }, [enterFullscreen, fullscreenSupported, recordings]);
    const [recordingSetupCompleted, setRecordingSetupCompleted] =
        useState(false);
    const recordingsNeedAttention = recordingNeedsAttention(
        recordings.cameraStatus,
        recordings.screenStatus,
    );
    const recordingUiSuppressed = submitting;
    const showRecordingWarning =
        !violation &&
        !proctoringDisabled &&
        !recordingUiSuppressed &&
        !recordingSetupCompleted &&
        recordingsNeedAttention;
    const showRecordingMessage =
        !violation &&
        !proctoringDisabled &&
        !recordingUiSuppressed &&
        recordingSetupCompleted &&
        recordingsNeedAttention;
    const hideAssessmentContent =
        !proctoringDisabled &&
        !recordingUiSuppressed &&
        recordingsNeedAttention;

    const mcqCount = orderedQuestions.filter(
        (question) => question.type === 'mcq',
    ).length;
    const codingCount = orderedQuestions.length - mcqCount;
    const currentQuestion = orderedQuestions[currentQuestionIndex] ?? null;
    const answeredCount = orderedQuestions.filter((question) =>
        isQuestionAnswered(question, data.answers, codingDrafts),
    ).length;
    const isLastQuestion =
        currentQuestionIndex === Math.max(orderedQuestions.length - 1, 0);
    const questionNavigationDisabled =
        expired || processing || submitting || navigating;

    useEffect(() => {
        const timer = window.setInterval(() => {
            setRemainingSeconds((seconds) => Math.max(seconds - 1, 0));
        }, 1000);

        return () => window.clearInterval(timer);
    }, []);

    useEffect(() => {
        if (
            !recordingSetupCompleted &&
            recordings.cameraStatus === 'recording' &&
            recordings.screenStatus === 'recording'
        ) {
            setRecordingSetupCompleted(true);
        }
    }, [
        recordingSetupCompleted,
        recordings.cameraStatus,
        recordings.screenStatus,
    ]);

    useEffect(() => {
        setCurrentQuestionIndex((current) =>
            clampQuestionIndex(current, orderedQuestions.length),
        );
    }, [orderedQuestions.length]);

    useEffect(() => {
        const firstErrorIndex = orderedQuestions.findIndex((question) =>
            hasQuestionError(question, formErrors),
        );

        if (firstErrorIndex >= 0) {
            setCurrentQuestionIndex(firstErrorIndex);
        }
    }, [formErrors, orderedQuestions]);

    const updateCodingDraft = useCallback(
        (questionId: number, draft: CodingQuestionDraft) => {
            setCodingDrafts((current) => ({
                ...current,
                [questionId]: draft,
            }));
        },
        [],
    );

    const saveMcqAnswers = useCallback(async (): Promise<boolean> => {
        setNavigationError(null);

        try {
            await axios.post(attemptRoute(attempt, 'save'), {
                answers: data.answers,
            });

            return true;
        } catch (error) {
            setNavigationError(
                validationMessage(
                    error,
                    'Unable to save your multiple-choice answers right now.',
                ),
            );

            return false;
        }
    }, [attempt, data.answers]);

    const saveCodingDraft = useCallback(
        async (question: CodingQuestion): Promise<boolean> => {
            const draft = codingDrafts[question.id];
            const answer = draft ?? question.saved_answer;

            if (!answer?.language) {
                return true;
            }

            setNavigationError(null);

            try {
                await axios.post(codingAnswerRoute(attempt), {
                    question_id: question.id,
                    language: answer.language,
                    submitted_code: answer.submitted_code ?? null,
                });

                return true;
            } catch (error) {
                setNavigationError(
                    validationMessage(
                        error,
                        'Unable to save your coding answer right now.',
                    ),
                );

                return false;
            }
        },
        [attempt, codingDrafts],
    );

    const saveAllCodingDrafts = useCallback(async (): Promise<boolean> => {
        for (const question of orderedQuestions) {
            if (question.type !== 'coding') {
                continue;
            }

            const saved = await saveCodingDraft(question);

            if (!saved) {
                return false;
            }
        }

        return true;
    }, [orderedQuestions, saveCodingDraft]);

    const persistCurrentQuestion = useCallback(async (): Promise<boolean> => {
        if (!currentQuestion) {
            return true;
        }

        clearErrors();

        const mcqSaved = await saveMcqAnswers();

        if (!mcqSaved) {
            return false;
        }

        if (currentQuestion.type === 'coding') {
            return saveCodingDraft(currentQuestion);
        }

        return true;
    }, [clearErrors, currentQuestion, saveCodingDraft, saveMcqAnswers]);

    const navigateToQuestion = useCallback(
        async (nextIndex: number) => {
            if (
                nextIndex === currentQuestionIndex ||
                nextIndex < 0 ||
                nextIndex >= orderedQuestions.length
            ) {
                return;
            }

            setNavigating(true);

            try {
                const saved = await persistCurrentQuestion();

                if (saved) {
                    setCurrentQuestionIndex(nextIndex);
                }
            } finally {
                setNavigating(false);
            }
        },
        [
            currentQuestionIndex,
            orderedQuestions.length,
            persistCurrentQuestion,
        ],
    );

    const submit: FormEventHandler = async (event) => {
        event.preventDefault();

        setSubmitting(true);
        setNavigationError(null);

        try {
            clearErrors();

            const mcqSaved = await saveMcqAnswers();

            if (!mcqSaved) {
                setSubmitting(false);
                return;
            }

            const codingSaved = await saveAllCodingDrafts();

            if (!codingSaved) {
                setSubmitting(false);
                return;
            }

            post(attemptRoute(attempt, 'submit'), {
                onSuccess: async () => {
                    await recordings.stopAllRecordings('attempt_submitted');
                },
                onError: () => setSubmitting(false),
                onFinish: () => setSubmitting(false),
            });
        } catch {
            setSubmitting(false);
        }
    };

    const selectAnswer = (questionId: number, optionId: number) => {
        setData('answers', {
            ...data.answers,
            [questionId]: optionId,
        });
        clearErrors(`answers.${questionId}`);
        setNavigationError(null);
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

            {violation && (
                <ProctoringViolationOverlay
                    violation={violation}
                    onAcknowledgeAndReturnToFullscreen={
                        acknowledgeAndReturnToFullscreen
                    }
                />
            )}

            {showRecordingWarning && (
                <RecordingPermissionOverlay
                    recordings={recordings}
                    onStartScreen={startScreenAndReturnToFullscreen}
                />
            )}

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div className="flex flex-wrap items-start justify-between gap-6">
                            <div>
                                <p className="text-sm font-medium uppercase tracking-wide text-gray-500">
                                    Assessment attempt
                                </p>
                                <h1 className="mt-2 text-2xl font-semibold text-gray-900">
                                    {test.title}
                                </h1>
                                <p className="mt-3 max-w-3xl text-sm text-gray-600">
                                    Questions appear one at a time. Multiple-choice
                                    questions are shown first, followed by coding
                                    questions. Moving between questions keeps your
                                    current progress.
                                </p>
                            </div>
                            <div className="rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
                                <p className="font-semibold">
                                    Question {currentQuestionIndex + 1} of{' '}
                                    {Math.max(orderedQuestions.length, 1)}
                                </p>
                                <p className="mt-1">
                                    {answeredCount} answered
                                </p>
                            </div>
                        </div>

                        <dl className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                            <SummaryStat
                                label="Duration"
                                value={`${test.duration_minutes} minutes`}
                            />
                            <SummaryStat
                                label="Pass percentage"
                                value={String(test.pass_mark)}
                            />
                            <SummaryStat
                                label="Questions"
                                value={String(orderedQuestions.length)}
                            />
                            <SummaryStat
                                label="MCQs"
                                value={String(mcqCount)}
                            />
                            <SummaryStat
                                label="Coding"
                                value={String(codingCount)}
                            />
                            <SummaryStat
                                label="Time remaining"
                                value={timeRemaining}
                            />
                        </dl>

                        {expired && (
                            <p className="mt-4 rounded-md bg-red-50 p-3 text-sm font-medium text-red-700">
                                Time is over. Answers can no longer be saved or
                                submitted.
                            </p>
                        )}
                        <div className="mt-4 rounded-md border border-gray-200 bg-gray-50 p-3 text-sm">
                            <p className="font-medium text-gray-700">
                                Proctoring controls active
                            </p>
                            <p className="mt-1 text-gray-500">
                                Fullscreen is required. Copy, paste, right click,
                                drag/drop, and restricted shortcuts are blocked.
                            </p>
                        </div>
                        {latestBlockedAction && (
                            <p className="mt-3 rounded-md bg-amber-50 p-3 text-sm font-medium text-amber-800">
                                {latestBlockedAction}
                            </p>
                        )}
                        {showRecordingMessage && (
                            <RecordingStoppedMessage
                                recordings={recordings}
                                onStartScreen={startScreenAndReturnToFullscreen}
                            />
                        )}
                    </div>

                    {hideAssessmentContent ? (
                        <RecordingLockedNotice />
                    ) : currentQuestion ? (
                        <form onSubmit={submit} className="space-y-6">
                            <QuestionProgressStrip
                                questions={orderedQuestions}
                                currentQuestionIndex={currentQuestionIndex}
                                answers={data.answers}
                                codingDrafts={codingDrafts}
                                onSelectQuestion={(index) =>
                                    void navigateToQuestion(index)
                                }
                                disabled={questionNavigationDisabled}
                            />

                            <QuestionPanel
                                attempt={attempt}
                                question={currentQuestion}
                                questionNumber={currentQuestionIndex + 1}
                                selectedAnswer={data.answers[currentQuestion.id]}
                                draftAnswer={codingDrafts[currentQuestion.id]}
                                formErrors={formErrors}
                                disabled={questionNavigationDisabled}
                                onDraftChange={updateCodingDraft}
                                onSelectAnswer={selectAnswer}
                            />

                            <div className="flex flex-wrap items-center justify-between gap-4 bg-white p-6 shadow-sm sm:rounded-lg">
                                <div className="space-y-2">
                                    <InputError
                                        message={navigationError ?? formErrors.attempt}
                                        className="mt-0"
                                    />
                                    <p className="text-sm text-gray-500">
                                        {currentQuestion.type === 'coding'
                                            ? 'Use the editor on the right, run visible test cases, then move to the next question.'
                                            : 'Select one option, then move forward when you are ready.'}
                                    </p>
                                </div>

                                <div className="flex flex-wrap justify-end gap-3">
                                    <SecondaryButton
                                        type="button"
                                        onClick={() =>
                                            void navigateToQuestion(
                                                currentQuestionIndex - 1,
                                            )
                                        }
                                        disabled={
                                            currentQuestionIndex === 0 ||
                                            questionNavigationDisabled
                                        }
                                    >
                                        Previous
                                    </SecondaryButton>

                                    {isLastQuestion ? (
                                        <PrimaryButton
                                            disabled={questionNavigationDisabled}
                                        >
                                            Submit test
                                        </PrimaryButton>
                                    ) : (
                                        <PrimaryButton
                                            type="button"
                                            onClick={() =>
                                                void navigateToQuestion(
                                                    currentQuestionIndex + 1,
                                                )
                                            }
                                            disabled={questionNavigationDisabled}
                                        >
                                            Next
                                        </PrimaryButton>
                                    )}
                                </div>
                            </div>
                        </form>
                    ) : (
                        <section className="rounded-md border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm">
                            No questions are available for this assessment yet.
                        </section>
                    )}
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
    draftAnswer,
    formErrors,
    disabled,
    onDraftChange,
    onSelectAnswer,
}: {
    attempt: Attempt;
    question: Question;
    questionNumber: number;
    selectedAnswer: number | string | undefined;
    draftAnswer: CodingQuestionDraft | undefined;
    formErrors: Record<string, string>;
    disabled: boolean;
    onDraftChange: (questionId: number, draft: CodingQuestionDraft) => void;
    onSelectAnswer: (questionId: number, optionId: number) => void;
}) {
    if (question.type === 'coding') {
        return (
            <CodingQuestionPanel
                key={question.id}
                question={question}
                questionNumber={questionNumber}
                saveUrl={codingAnswerRoute(attempt)}
                runUrl={codingRunRoute(attempt)}
                disabled={disabled}
                draftAnswer={draftAnswer}
                onDraftChange={onDraftChange}
                submitError={formErrors[`coding_answers.${question.id}`]}
            />
        );
    }

    return (
        <div className="bg-white p-6 shadow-sm sm:rounded-lg">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-medium uppercase tracking-wide text-indigo-600">
                        Multiple choice
                    </p>
                    <h3 className="mt-2 text-lg font-semibold text-gray-900">
                        Question {questionNumber}
                    </h3>
                </div>
                <span className="rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-600">
                    {question.marks} marks
                </span>
            </div>

            <p className="mt-5 whitespace-pre-line text-base leading-7 text-gray-800">
                {question.body}
            </p>

            <div className="mt-6 space-y-3">
                {question.options.map((option) => {
                    const selected = selectedAnswer === option.id;

                    return (
                        <label
                            key={option.id}
                            className={
                                'flex cursor-pointer items-start gap-3 rounded-lg border p-4 text-sm transition ' +
                                (selected
                                    ? 'border-indigo-500 bg-indigo-50 text-indigo-950'
                                    : 'border-gray-200 bg-white text-gray-800 hover:border-gray-300')
                            }
                        >
                            <input
                                type="radio"
                                name={`answers.${question.id}`}
                                value={option.id}
                                checked={selected}
                                onChange={() =>
                                    onSelectAnswer(question.id, option.id)
                                }
                                className="mt-1"
                                disabled={disabled}
                            />
                            <span className="leading-6">{option.body}</span>
                        </label>
                    );
                })}
            </div>

            <InputError
                message={
                    formErrors[`answers.${question.id}`] ?? formErrors.answers
                }
                className="mt-4"
            />
        </div>
    );
}

function QuestionProgressStrip({
    questions,
    currentQuestionIndex,
    answers,
    codingDrafts,
    onSelectQuestion,
    disabled,
}: {
    questions: Question[];
    currentQuestionIndex: number;
    answers: Record<string, number | string>;
    codingDrafts: Record<number, CodingQuestionDraft>;
    onSelectQuestion: (index: number) => void;
    disabled: boolean;
}) {
    return (
        <div className="bg-white p-4 shadow-sm sm:rounded-lg">
            <div className="flex flex-wrap gap-3">
                {questions.map((question, index) => {
                    const active = index === currentQuestionIndex;
                    const answered = isQuestionAnswered(
                        question,
                        answers,
                        codingDrafts,
                    );

                    return (
                        <button
                            key={question.id}
                            type="button"
                            onClick={() => onSelectQuestion(index)}
                            disabled={disabled}
                            className={
                                'min-w-[112px] rounded-lg border px-3 py-2 text-left text-sm transition ' +
                                (active
                                    ? 'border-indigo-500 bg-indigo-50 text-indigo-950'
                                    : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300')
                            }
                        >
                            <span className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                {question.type === 'coding' ? 'Coding' : 'MCQ'}
                            </span>
                            <span className="mt-1 block font-semibold">
                                Question {index + 1}
                            </span>
                            <span
                                className={
                                    'mt-1 block text-xs ' +
                                    (answered
                                        ? 'text-green-700'
                                        : 'text-gray-500')
                                }
                            >
                                {answered ? 'Answered' : 'Pending'}
                            </span>
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

function SummaryStat({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-sm font-medium text-gray-500">{label}</dt>
            <dd className="mt-1 text-sm font-medium text-gray-900">{value}</dd>
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

function ProctoringViolationOverlay({
    violation,
    onAcknowledgeAndReturnToFullscreen,
}: {
    violation: {
        title: string;
        message: string;
    };
    onAcknowledgeAndReturnToFullscreen: () => void;
}) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/80 px-4">
            <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                <p className="text-sm font-medium uppercase text-red-600">
                    Proctoring interruption
                </p>
                <h3 className="mt-2 text-lg font-semibold text-gray-900">
                    {violation.title}
                </h3>
                <p className="mt-3 text-sm text-gray-700">
                    {violation.message}
                </p>
                <p className="mt-2 text-sm text-gray-500">
                    Your timer continues while this notice is open.
                </p>

                <div className="mt-6 flex justify-end">
                    <PrimaryButton
                        type="button"
                        className="w-full justify-center whitespace-normal text-center leading-5 tracking-normal sm:w-auto"
                        onClick={onAcknowledgeAndReturnToFullscreen}
                    >
                        I understand and go back to fullscreen
                    </PrimaryButton>
                </div>
            </div>
        </div>
    );
}

function RecordingPermissionOverlay({
    recordings,
    onStartScreen,
}: {
    recordings: ProctoringRecordingControls;
    onStartScreen: () => void;
}) {
    const cameraNeedsAttention = recordings.cameraStatus !== 'recording';
    const screenNeedsAttention = recordings.screenStatus !== 'recording';

    return (
        <div className="fixed inset-0 z-40 flex items-center justify-center bg-gray-950/70 px-4">
            <div className="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
                <p className="text-sm font-medium uppercase text-amber-600">
                    Proctoring recording required
                </p>
                <h3 className="mt-2 text-lg font-semibold text-gray-900">
                    Do not change camera or screen settings
                </h3>
                <div className="mt-4 space-y-3 text-sm text-gray-700">
                    <p>
                        Camera and screen recording are important for this
                        assessment and must remain active during the test.
                    </p>
                    {cameraNeedsAttention && (
                        <p>
                            Camera recording is not active. Please allow camera
                            access again.
                        </p>
                    )}
                    {screenNeedsAttention && (
                        <p>
                            Screen recording is not active. Please start screen
                            sharing.
                        </p>
                    )}
                    <p className="text-gray-500">
                        Your timer continues while this notice is open.
                    </p>
                </div>

                <div className="mt-6 flex flex-wrap justify-end gap-3">
                    {cameraNeedsAttention && (
                        <SecondaryButton
                            type="button"
                            disabled={recordings.cameraStatus === 'requesting'}
                            onClick={() => void recordings.startCamera()}
                        >
                            {recordings.cameraStatus === 'idle'
                                ? 'Start camera'
                                : 'Retry camera'}
                        </SecondaryButton>
                    )}
                    {screenNeedsAttention && (
                        <SecondaryButton
                            type="button"
                            disabled={recordings.screenStatus === 'requesting'}
                            onClick={onStartScreen}
                        >
                            Start screen sharing
                        </SecondaryButton>
                    )}
                </div>
            </div>
        </div>
    );
}

function RecordingStoppedMessage({
    recordings,
    onStartScreen,
}: {
    recordings: ProctoringRecordingControls;
    onStartScreen: () => void;
}) {
    const cameraNeedsAttention = recordings.cameraStatus !== 'recording';
    const screenNeedsAttention = recordings.screenStatus !== 'recording';

    return (
        <div className="mt-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
            <p className="font-medium">
                You cannot stop camera or screen sharing during the assessment.
            </p>
            <p className="mt-1">
                Please restart the required recording immediately. Your attempt
                timer continues running.
            </p>
            <div className="mt-3 flex flex-wrap gap-2">
                {cameraNeedsAttention && (
                    <SecondaryButton
                        type="button"
                        disabled={recordings.cameraStatus === 'requesting'}
                        onClick={() => void recordings.startCamera()}
                    >
                        Restart camera
                    </SecondaryButton>
                )}
                {screenNeedsAttention && (
                    <SecondaryButton
                        type="button"
                        disabled={recordings.screenStatus === 'requesting'}
                        onClick={onStartScreen}
                    >
                        Restart screen sharing
                    </SecondaryButton>
                )}
            </div>
        </div>
    );
}

function RecordingLockedNotice() {
    return (
        <section className="rounded-md border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900 shadow-sm">
            <p className="font-medium">Assessment content is hidden.</p>
            <p className="mt-1">
                Camera and screen recording must be active before questions,
                save, and submit controls are shown again.
            </p>
        </section>
    );
}

function recordingNeedsAttention(
    cameraStatus: RecordingStatus,
    screenStatus: RecordingStatus,
): boolean {
    return cameraStatus !== 'recording' || screenStatus !== 'recording';
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

function questionTypePriority(type: Question['type']): number {
    return type === 'mcq' ? 0 : 1;
}

function findInitialQuestionIndex(
    questions: Question[],
    savedAnswers: Record<string, number>,
): number {
    const firstPendingIndex = questions.findIndex((question) => {
        if (question.type === 'coding') {
            return blank(question.saved_answer?.submitted_code);
        }

        return !savedAnswers[String(question.id)];
    });

    return firstPendingIndex >= 0 ? firstPendingIndex : 0;
}

function clampQuestionIndex(current: number, questionCount: number): number {
    if (questionCount <= 0) {
        return 0;
    }

    return Math.min(Math.max(current, 0), questionCount - 1);
}

function hasQuestionError(
    question: Question,
    formErrors: Record<string, string>,
): boolean {
    return Boolean(
        formErrors[`answers.${question.id}`] ||
            formErrors[`coding_answers.${question.id}`],
    );
}

function isQuestionAnswered(
    question: Question,
    answers: Record<string, number | string>,
    codingDrafts: Record<number, CodingQuestionDraft>,
): boolean {
    if (question.type === 'coding') {
        const draft = codingDrafts[question.id];

        if (draft) {
            return !blank(draft.submitted_code);
        }

        return !blank(question.saved_answer?.submitted_code);
    }

    return Boolean(answers[String(question.id)]);
}

function blank(value: string | null | undefined): boolean {
    return value === null || value === undefined || value.trim() === '';
}

function validationMessage(error: unknown, fallback: string): string {
    if (axios.isAxiosError(error) && error.response?.data?.errors) {
        const validationErrors = error.response.data.errors as Record<
            string,
            string[]
        >;
        const firstError = Object.values(validationErrors)[0]?.[0];

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

    return fallback;
}
