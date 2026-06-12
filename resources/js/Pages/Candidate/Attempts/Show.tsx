import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useEffect, useMemo, useState } from 'react';

type Attempt = {
    id: number;
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

type Question = {
    id: number;
    body: string;
    marks: number;
    options: {
        id: number;
        body: string;
    }[];
};

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
    const [remainingSeconds, setRemainingSeconds] = useState(() =>
        secondsUntilExpiry(attempt.expires_at, attempt.server_now),
    );
    const expired = remainingSeconds <= 0;
    const timeRemaining = useMemo(
        () => formatRemainingTime(remainingSeconds),
        [remainingSeconds],
    );

    useEffect(() => {
        const timer = window.setInterval(() => {
            setRemainingSeconds((seconds) => Math.max(seconds - 1, 0));
        }, 1000);

        return () => window.clearInterval(timer);
    }, []);

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('candidate.attempts.submit', attempt.id));
    };

    const save = () => {
        post(route('candidate.attempts.answers.save', attempt.id), {
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
        <AuthenticatedLayout
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
                            MCQ attempt
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
                    </div>

                    <form onSubmit={submit} className="space-y-6">
                        {questions.map((question, questionIndex) => (
                            <div
                                key={question.id}
                                className="bg-white p-6 shadow-sm sm:rounded-lg"
                            >
                                <div className="flex flex-wrap items-start justify-between gap-4">
                                    <h3 className="text-base font-semibold text-gray-900">
                                        Question {questionIndex + 1}
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
                                                checked={
                                                    data.answers[
                                                        question.id
                                                    ] === option.id
                                                }
                                                onChange={() =>
                                                    selectAnswer(
                                                        question.id,
                                                        option.id,
                                                    )
                                                }
                                                className="mt-1"
                                            />
                                            <span>{option.body}</span>
                                        </label>
                                    ))}
                                </div>

                                <InputError
                                    message={
                                        formErrors[
                                            `answers.${question.id}`
                                        ] ?? formErrors.answers
                                    }
                                    className="mt-3"
                                />
                            </div>
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
                                    disabled={processing || expired}
                                >
                                    Save answers
                                </SecondaryButton>
                                <PrimaryButton disabled={processing || expired}>
                                    Submit test
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
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
