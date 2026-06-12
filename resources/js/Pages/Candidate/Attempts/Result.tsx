import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

type Attempt = {
    id: number;
    status: string;
    score: number;
    max_score: number;
    total_marks: number;
    percentage: string | number | null;
    passed: boolean | null;
    started_at: string | null;
    submitted_at: string | null;
    expires_at: string | null;
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

type Answer = {
    id: number;
    question: {
        id: number;
        body: string;
        marks: number;
    };
    selected_option: {
        id: number;
        body: string;
    } | null;
    is_correct: boolean;
    score: number;
};

export default function Result({
    attempt,
    test,
    answers,
}: {
    attempt: Attempt;
    test: Test;
    answers: Answer[];
}) {
    const maxScore = attempt.max_score || attempt.total_marks;
    const percentage = Number(attempt.percentage ?? 0);
    const passed = attempt.passed ?? percentage >= test.pass_mark;

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Test Result
                </h2>
            }
        >
            <Head title={`${test.title} Result`} />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <p className="text-sm font-medium uppercase text-gray-500">
                            Submitted
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold text-gray-900">
                            {test.title}
                        </h1>

                        <dl className="mt-6 grid gap-4 sm:grid-cols-3">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Score
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {attempt.score} / {maxScore}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Percentage
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {percentage}%
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Status
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900">
                                    {passed ? 'Passed' : 'Failed'}
                                </dd>
                            </div>
                        </dl>

                        <Link
                            href={route('candidate.tests.show', test.id)}
                            className="mt-6 inline-flex rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700"
                        >
                            Back to test
                        </Link>
                    </div>

                    <div className="space-y-4">
                        {answers.map((answer, index) => (
                            <div
                                key={answer.id}
                                className="bg-white p-6 shadow-sm sm:rounded-lg"
                            >
                                <div className="flex flex-wrap items-start justify-between gap-4">
                                    <h3 className="text-base font-semibold text-gray-900">
                                        Question {index + 1}
                                    </h3>
                                    <span className="text-sm text-gray-500">
                                        {answer.score} /{' '}
                                        {answer.question.marks}
                                    </span>
                                </div>

                                <p className="mt-3 whitespace-pre-line text-sm text-gray-800">
                                    {answer.question.body}
                                </p>
                                <p className="mt-4 text-sm text-gray-700">
                                    Selected answer:{' '}
                                    {answer.selected_option?.body ??
                                        'No answer'}
                                </p>
                                <p
                                    className={
                                        answer.is_correct
                                            ? 'mt-2 text-sm font-medium text-green-700'
                                            : 'mt-2 text-sm font-medium text-red-700'
                                    }
                                >
                                    {answer.is_correct
                                        ? 'Correct'
                                        : 'Incorrect'}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
