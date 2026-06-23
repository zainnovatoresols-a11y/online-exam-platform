import DangerButton from '@/Components/DangerButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { DragEvent, useEffect, useState } from 'react';

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

type DropIndicator = {
    questionId: number;
    position: 'before' | 'after';
};

type Props = {
    test: Test;
    canManageQuestions: boolean;
    canManageCodingQuestions: boolean;
    canReorderQuestions: boolean;
    questions: Question[];
};

export default function Index({
    test,
    canManageQuestions,
    canManageCodingQuestions,
    canReorderQuestions,
    questions,
}: Props) {
    const [orderedQuestions, setOrderedQuestions] = useState<Question[]>(questions);
    const [draggedQuestionId, setDraggedQuestionId] = useState<number | null>(
        null,
    );
    const [dropIndicator, setDropIndicator] = useState<DropIndicator | null>(
        null,
    );
    const [isSavingOrder, setIsSavingOrder] = useState(false);

    useEffect(() => {
        setOrderedQuestions(questions);
    }, [questions]);

    const reorderingEnabled =
        canReorderQuestions && orderedQuestions.length > 1;

    const destroy = (question: Question) => {
        const routeName =
            question.type === 'coding'
                ? 'admin.tests.coding-questions.destroy'
                : 'admin.tests.questions.destroy';

        router.delete(route(routeName, [test.id, question.id]));
    };

    const resetDragState = () => {
        setDraggedQuestionId(null);
        setDropIndicator(null);
    };

    const handleDragStart = (
        event: DragEvent<HTMLDivElement>,
        questionId: number,
    ) => {
        if (!reorderingEnabled || isSavingOrder) {
            return;
        }

        if (isInteractiveDragTarget(event.target)) {
            event.preventDefault();
            return;
        }

        setDraggedQuestionId(questionId);
        setDropIndicator(null);
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(questionId));
    };

    const handleDragOver = (
        event: DragEvent<HTMLDivElement>,
        questionId: number,
    ) => {
        if (!reorderingEnabled || isSavingOrder) {
            return;
        }

        event.preventDefault();

        if (draggedQuestionId === null || draggedQuestionId === questionId) {
            setDropIndicator(null);
            return;
        }

        const rect = event.currentTarget.getBoundingClientRect();
        const position =
            event.clientY - rect.top < rect.height / 2 ? 'before' : 'after';

        setDropIndicator({ questionId, position });
        event.dataTransfer.dropEffect = 'move';
    };

    const handleDrop = (
        event: DragEvent<HTMLDivElement>,
        targetQuestionId: number,
    ) => {
        if (!reorderingEnabled || isSavingOrder) {
            return;
        }

        event.preventDefault();

        const sourceQuestionId =
            draggedQuestionId ?? Number(event.dataTransfer.getData('text/plain'));

        if (!Number.isInteger(sourceQuestionId)) {
            resetDragState();
            return;
        }

        const targetPosition =
            dropIndicator?.questionId === targetQuestionId
                ? dropIndicator.position
                : 'after';

        const previousQuestions = orderedQuestions;
        const nextQuestions = reorderQuestions(
            previousQuestions,
            sourceQuestionId,
            targetQuestionId,
            targetPosition,
        );

        resetDragState();

        if (sameQuestionSequence(previousQuestions, nextQuestions)) {
            return;
        }

        setOrderedQuestions(nextQuestions);

        router.patch(
            route('admin.tests.questions.reorder', test.id),
            {
                question_ids: nextQuestions.map((question) => question.id),
            },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
                onStart: () => setIsSavingOrder(true),
                onError: () => setOrderedQuestions(previousQuestions),
                onFinish: () => setIsSavingOrder(false),
            },
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
                        {reorderingEnabled && (
                            <div className="rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600 shadow-sm">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <p>
                                        Drag a question card to update its
                                        order.
                                    </p>
                                    {isSavingOrder && (
                                        <span className="font-medium text-gray-900">
                                            Saving order...
                                        </span>
                                    )}
                                </div>
                            </div>
                        )}

                        {orderedQuestions.map((question) => (
                            <div
                                key={question.id}
                                draggable={reorderingEnabled && !isSavingOrder}
                                onDragStart={(event) =>
                                    handleDragStart(event, question.id)
                                }
                                onDragOver={(event) =>
                                    handleDragOver(event, question.id)
                                }
                                onDrop={(event) =>
                                    handleDrop(event, question.id)
                                }
                                onDragEnd={resetDragState}
                                className={`bg-white p-6 shadow-sm transition sm:rounded-lg ${
                                    reorderingEnabled
                                        ? 'cursor-grab active:cursor-grabbing'
                                        : ''
                                } ${
                                    draggedQuestionId === question.id
                                        ? 'opacity-60'
                                        : ''
                                } ${dropIndicatorClass(
                                    dropIndicator,
                                    question.id,
                                )}`}
                            >
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-start justify-between gap-4">
                                        <div>
                                            <p className="text-sm font-medium uppercase text-gray-500">
                                                {formatLabel(question.type)} -
                                                Marks: {question.marks} -
                                                Order: {question.order}
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
                            </div>
                        ))}

                        {orderedQuestions.length === 0 && (
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

function sameQuestionSequence(
    currentQuestions: Question[],
    nextQuestions: Question[],
): boolean {
    if (currentQuestions.length !== nextQuestions.length) {
        return false;
    }

    return currentQuestions.every(
        (question, index) => question.id === nextQuestions[index]?.id,
    );
}

function reorderQuestions(
    questions: Question[],
    sourceQuestionId: number,
    targetQuestionId: number,
    position: 'before' | 'after',
): Question[] {
    if (sourceQuestionId === targetQuestionId) {
        return questions;
    }

    const nextQuestions = [...questions];
    const sourceIndex = nextQuestions.findIndex(
        (question) => question.id === sourceQuestionId,
    );
    const targetIndex = nextQuestions.findIndex(
        (question) => question.id === targetQuestionId,
    );

    if (sourceIndex === -1 || targetIndex === -1) {
        return questions;
    }

    const [movedQuestion] = nextQuestions.splice(sourceIndex, 1);
    const adjustedTargetIndex = nextQuestions.findIndex(
        (question) => question.id === targetQuestionId,
    );

    if (adjustedTargetIndex === -1) {
        return questions;
    }

    const insertIndex =
        position === 'before' ? adjustedTargetIndex : adjustedTargetIndex + 1;

    nextQuestions.splice(insertIndex, 0, movedQuestion);

    return nextQuestions.map((question, index) => ({
        ...question,
        order: index + 1,
    }));
}

function dropIndicatorClass(
    indicator: DropIndicator | null,
    questionId: number,
): string {
    if (indicator?.questionId !== questionId) {
        return '';
    }

    return indicator.position === 'before'
        ? 'border-t-4 border-t-gray-900'
        : 'border-b-4 border-b-gray-900';
}

function isInteractiveDragTarget(target: EventTarget): boolean {
    return target instanceof HTMLElement
        && target.closest('a, button, input, textarea, select') !== null;
}

function languageLabel(values: string[]): string {
    if (values.length === 0) {
        return 'No languages';
    }

    return values
        .map((value) => (value === 'cpp' ? 'C++' : formatLabel(value)))
        .join(', ');
}
