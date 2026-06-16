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
    type: 'mcq' | 'coding';
    body: string;
    marks: number;
    order: number;
    difficulty: string | null;
    supported_languages: string[];
    options_count: number;
    test_cases_count: number;
    options: Option[];
};

type Props = {
    test: Test;
    canManageQuestions: boolean;
    canManageCodingQuestions: boolean;
    questions: Question[];
};

export default function Index({
    test,
    canManageQuestions,
    canManageCodingQuestions,
    questions,
}: Props) {
    const destroy = (question: Question) => {
        const routeName =
            question.type === 'coding'
                ? 'admin.tests.coding-questions.destroy'
                : 'admin.tests.questions.destroy';

        router.delete(
            route(routeName, [test.id, question.id]),
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
                        <div className="flex flex-wrap gap-3">
                            {canManageQuestions && (
                                <Link
                                    href={route(
                                        'admin.tests.questions.create',
                                        test.id,
                                    )}
                                    className="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white"
                                >
                                    Add MCQ
                                </Link>
                            )}
                            {canManageCodingQuestions && (
                                <Link
                                    href={route(
                                        'admin.tests.coding-questions.create',
                                        test.id,
                                    )}
                                    className="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700"
                                >
                                    Add Coding Question
                                </Link>
                            )}
                        </div>
                    </div>

                    <div className="space-y-4">
                        {questions.map((question) => (
                            <div
                                key={question.id}
                                className="bg-white p-6 shadow-sm sm:rounded-lg"
                            >
                                <div className="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <p className="text-sm font-medium uppercase text-gray-500">
                                            {formatLabel(question.type)} ·
                                            Marks: {question.marks} · Order:{' '}
                                            {question.order}
                                        </p>
                                        <h4 className="mt-2 text-base font-semibold text-gray-900">
                                            {preview(question.body)}
                                        </h4>
                                        <div className="mt-3 flex flex-wrap gap-2 text-xs text-gray-600">
                                            {question.type === 'mcq' && (
                                                <span className="rounded-full bg-gray-100 px-2.5 py-1">
                                                    {question.options_count}{' '}
                                                    options
                                                </span>
                                            )}
                                            {question.type === 'coding' && (
                                                <>
                                                    <span className="rounded-full bg-gray-100 px-2.5 py-1">
                                                        {formatLabel(
                                                            question.difficulty ??
                                                                'difficulty',
                                                        )}
                                                    </span>
                                                    <span className="rounded-full bg-gray-100 px-2.5 py-1">
                                                        {
                                                            question.test_cases_count
                                                        }{' '}
                                                        test cases
                                                    </span>
                                                    <span className="rounded-full bg-gray-100 px-2.5 py-1">
                                                        {languageLabel(
                                                            question.supported_languages,
                                                        )}
                                                    </span>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                    {canManage(question) && (
                                        <div className="flex gap-3">
                                            <Link
                                                href={editRoute(question)}
                                                className="text-sm font-medium text-gray-900 underline"
                                            >
                                                Edit
                                            </Link>
                                            <DangerButton
                                                type="button"
                                                onClick={() =>
                                                    destroy(question)
                                                }
                                            >
                                                Delete
                                            </DangerButton>
                                        </div>
                                    )}
                                </div>

                                {question.type === 'mcq' && (
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
                                )}
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

    function canManage(question: Question): boolean {
        return question.type === 'coding'
            ? canManageCodingQuestions
            : canManageQuestions;
    }

    function editRoute(question: Question): string {
        if (question.type === 'coding') {
            return route('admin.tests.coding-questions.edit', [
                test.id,
                question.id,
            ]);
        }

        return route('admin.tests.questions.edit', [test.id, question.id]);
    }
}

function preview(value: string): string {
    return value.length > 180 ? `${value.slice(0, 180)}...` : value;
}

function formatLabel(value: string): string {
    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function languageLabel(values: string[]): string {
    if (values.length === 0) {
        return 'No languages';
    }

    return values
        .map((value) => (value === 'cpp' ? 'C++' : formatLabel(value)))
        .join(', ');
}
