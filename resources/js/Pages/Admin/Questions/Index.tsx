import DangerButton from '@/Components/DangerButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

type Test = {
    id: number;
    title: string;
    status: string;
};

type Option = {
    id: number;
    body: string;
    is_correct: boolean;
};

type Question = {
    id: number;
    body: string;
    marks: number;
    options: Option[];
};

type Props = {
    test: Test;
    canManageQuestions: boolean;
    questions: Question[];
};

export default function Index({ test, canManageQuestions, questions }: Props) {
    const destroy = (questionId: number) => {
        router.delete(
            route('admin.tests.questions.destroy', [test.id, questionId]),
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Questions
                </h2>
            }
        >
            <Head title="Questions" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900">
                                {test.title}
                            </h3>
                            <p className="text-sm text-gray-600">
                                Status: {test.status}
                            </p>
                        </div>
                        {canManageQuestions && (
                            <Link
                                href={route(
                                    'admin.tests.questions.create',
                                    test.id,
                                )}
                                className="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white"
                            >
                                Add MCQ question
                            </Link>
                        )}
                    </div>

                    <div className="space-y-4">
                        {questions.map((question) => (
                            <div
                                key={question.id}
                                className="bg-white p-6 shadow-sm sm:rounded-lg"
                            >
                                <div className="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <p className="text-sm font-medium text-gray-500">
                                            Marks: {question.marks}
                                        </p>
                                        <h4 className="mt-2 text-base font-semibold text-gray-900">
                                            {question.body}
                                        </h4>
                                    </div>
                                    {canManageQuestions && (
                                        <div className="flex gap-3">
                                            <Link
                                                href={route(
                                                    'admin.tests.questions.edit',
                                                    [test.id, question.id],
                                                )}
                                                className="text-sm font-medium text-gray-900 underline"
                                            >
                                                Edit
                                            </Link>
                                            <DangerButton
                                                type="button"
                                                onClick={() =>
                                                    destroy(question.id)
                                                }
                                            >
                                                Delete
                                            </DangerButton>
                                        </div>
                                    )}
                                </div>

                                <ul className="mt-4 space-y-2">
                                    {question.options.map((option) => (
                                        <li
                                            key={option.id}
                                            className="flex items-start gap-2 text-sm text-gray-700"
                                        >
                                            <span className="mt-1 h-2 w-2 rounded-full bg-gray-400" />
                                            <span>
                                                {option.body}
                                                {option.is_correct && (
                                                    <strong className="ml-2 text-gray-900">
                                                        Correct
                                                    </strong>
                                                )}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}

                        {questions.length === 0 && (
                            <div className="bg-white p-6 text-sm text-gray-600 shadow-sm sm:rounded-lg">
                                No questions added yet.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
