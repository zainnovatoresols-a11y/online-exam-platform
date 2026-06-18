import InputError from '@/Components/InputError';
import CodingQuestionPanel, {
    CodingQuestion,
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
    const proctoringDisabled = expired || attempt.status !== 'in_progress';
    const { enterFullscreen, fullscreenActive, fullscreenSupported } =
        useProctoringEvents(attempt, proctoringDisabled);
    const {
        acknowledgeViolation,
        latestBlockedAction,
        violation,
    } = useProctoringGuards(attempt, proctoringDisabled);
    const acknowledgeAndReturnToFullscreen = useCallback(async () => {
        if (fullscreenSupported && ! fullscreenActive) {
            const enteredFullscreen = await enterFullscreen();

            if (! enteredFullscreen) {
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
    const recordings = useProctoringRecordings(attempt, proctoringDisabled);
    const recordingWarningKey = `${recordings.cameraStatus}:${recordings.screenStatus}`;
    const [recordingWarningDismissed, setRecordingWarningDismissed] =
        useState(false);
    const showRecordingWarning =
        ! violation &&
        ! proctoringDisabled &&
        ! recordingWarningDismissed &&
        recordingNeedsAttention(recordings.cameraStatus, recordings.screenStatus);
    const continueWithRecordingViolation = useCallback(() => {
        recordings.recordContinueWithViolation();
        setRecordingWarningDismissed(true);
    }, [recordings]);

    useEffect(() => {
        const timer = window.setInterval(() => {
            setRemainingSeconds((seconds) => Math.max(seconds - 1, 0));
        }, 1000);

        return () => window.clearInterval(timer);
    }, []);

    useEffect(() => {
        setRecordingWarningDismissed(false);
    }, [recordingWarningKey]);

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

            await recordings.stopAllRecordings('attempt_submitted');

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
                    onContinueWithViolation={
                        continueWithRecordingViolation
                    }
                />
            )}

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
                        <div className="mt-4 rounded-md border border-gray-200 bg-gray-50 p-3 text-sm">
                            <p className="font-medium text-gray-700">
                                Proctoring controls active
                            </p>
                            <p className="mt-1 text-gray-500">
                                Fullscreen is required. Copy, paste, right
                                click, drag/drop, and restricted shortcuts are
                                blocked.
                            </p>
                        </div>
                        {latestBlockedAction && (
                            <p className="mt-3 rounded-md bg-amber-50 p-3 text-sm font-medium text-amber-800">
                                {latestBlockedAction}
                            </p>
                        )}
                        <ProctoringRecordingPanel
                            recordings={recordings}
                            disabled={proctoringDisabled}
                        />
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

function ProctoringRecordingPanel({
    recordings,
    disabled,
}: {
    recordings: ProctoringRecordingControls;
    disabled: boolean;
}) {
    return (
        <div className="mt-4 rounded-md border border-gray-200 bg-white p-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold text-gray-900">
                        Screen and camera recording
                    </p>
                    <p className="mt-1 text-xs text-gray-500">
                        Video is saved in short private chunks for admin review.
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <RecordingStatusBadge
                        label="Camera"
                        status={recordings.cameraStatus}
                    />
                    <RecordingStatusBadge
                        label="Screen"
                        status={recordings.screenStatus}
                    />
                </div>
            </div>

            <div className="mt-4 grid gap-4 md:grid-cols-2">
                <RecordingPreviewCard
                    title="Camera"
                    videoRef={recordings.cameraVideoRef}
                    status={recordings.cameraStatus}
                    message={recordings.cameraMessage}
                    buttonLabel={
                        recordings.cameraStatus === 'recording'
                            ? 'Camera active'
                            : 'Start camera recording'
                    }
                    disabled={
                        disabled ||
                        recordings.cameraStatus === 'requesting' ||
                        recordings.cameraStatus === 'recording'
                    }
                    onStart={() => void recordings.startCamera()}
                />
                <RecordingPreviewCard
                    title="Screen"
                    videoRef={recordings.screenVideoRef}
                    status={recordings.screenStatus}
                    message={recordings.screenMessage}
                    buttonLabel={
                        recordings.screenStatus === 'recording'
                            ? 'Screen active'
                            : 'Start screen recording'
                    }
                    disabled={
                        disabled ||
                        recordings.screenStatus === 'requesting' ||
                        recordings.screenStatus === 'recording'
                    }
                    onStart={() => void recordings.startScreen()}
                />
            </div>
        </div>
    );
}

function RecordingPreviewCard({
    title,
    videoRef,
    status,
    message,
    buttonLabel,
    disabled,
    onStart,
}: {
    title: string;
    videoRef: React.RefObject<HTMLVideoElement>;
    status: RecordingStatus;
    message: string | null;
    buttonLabel: string;
    disabled: boolean;
    onStart: () => void;
}) {
    return (
        <div className="rounded-md border border-gray-200 bg-gray-50 p-3">
            <div className="flex items-center justify-between gap-3">
                <p className="text-sm font-medium text-gray-800">{title}</p>
                <RecordingStatusBadge status={status} />
            </div>
            <video
                ref={videoRef}
                autoPlay
                muted
                playsInline
                className="mt-3 aspect-video w-full rounded-md bg-gray-900 object-cover"
            />
            {message && (
                <p className="mt-2 text-xs font-medium text-amber-700">
                    {message}
                </p>
            )}
            <SecondaryButton
                type="button"
                className="mt-3"
                disabled={disabled}
                onClick={onStart}
            >
                {buttonLabel}
            </SecondaryButton>
        </div>
    );
}

function RecordingPermissionOverlay({
    recordings,
    onContinueWithViolation,
}: {
    recordings: ProctoringRecordingControls;
    onContinueWithViolation: () => void;
}) {
    const cameraNeedsAttention = recordings.cameraStatus !== 'recording';
    const screenNeedsAttention = recordings.screenStatus !== 'recording';

    return (
        <div className="fixed inset-0 z-40 flex items-center justify-center bg-gray-950/70 px-4">
            <div className="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
                <p className="text-sm font-medium uppercase text-amber-600">
                    Recording permission required
                </p>
                <h3 className="mt-2 text-lg font-semibold text-gray-900">
                    Camera and screen recording must stay active
                </h3>
                <div className="mt-4 space-y-3 text-sm text-gray-700">
                    {cameraNeedsAttention && (
                        <p>
                            Camera permission is required for this assessment.
                            Please allow camera access.
                        </p>
                    )}
                    {screenNeedsAttention && (
                        <p>
                            Screen sharing is required for this assessment.
                            Please share your entire screen where possible.
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
                            Retry camera
                        </SecondaryButton>
                    )}
                    {screenNeedsAttention && (
                        <SecondaryButton
                            type="button"
                            disabled={recordings.screenStatus === 'requesting'}
                            onClick={() => void recordings.startScreen()}
                        >
                            Start screen sharing
                        </SecondaryButton>
                    )}
                    <PrimaryButton
                        type="button"
                        className="whitespace-normal text-center leading-5 tracking-normal"
                        onClick={onContinueWithViolation}
                    >
                        I understand, continue with violation recorded
                    </PrimaryButton>
                </div>
            </div>
        </div>
    );
}

function RecordingStatusBadge({
    status,
    label,
}: {
    status: RecordingStatus;
    label?: string;
}) {
    const className =
        status === 'recording'
            ? 'bg-emerald-100 text-emerald-700'
            : status === 'requesting'
              ? 'bg-blue-100 text-blue-700'
              : ['denied', 'error', 'unavailable'].includes(status)
                ? 'bg-red-100 text-red-700'
                : 'bg-gray-100 text-gray-700';

    return (
        <span
            className={
                'inline-flex rounded-full px-2.5 py-1 text-xs font-medium ' +
                className
            }
        >
            {label ? `${label}: ` : ''}
            {formatRecordingStatus(status)}
        </span>
    );
}

function recordingNeedsAttention(
    cameraStatus: RecordingStatus,
    screenStatus: RecordingStatus,
): boolean {
    return cameraStatus !== 'recording' || screenStatus !== 'recording';
}

function formatRecordingStatus(status: RecordingStatus): string {
    return status
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
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
