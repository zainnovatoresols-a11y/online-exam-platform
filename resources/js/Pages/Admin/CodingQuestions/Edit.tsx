import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import CodingQuestionForm, {
    CodingQuestionFormData,
} from './Form';

type Test = {
    id: number;
    title: string;
};

type SelectOption = {
    value: string;
    label: string;
};

type CodingQuestion = {
    id: number;
    body: string;
    marks: number;
    order: number;
    difficulty: string;
    time_limit_ms: number;
    supported_languages: string[];
    starter_code: Record<string, string | null>;
    test_cases: {
        id: number;
        input: string | null;
        expected_output: string;
        is_hidden: boolean;
        points: number | null;
    }[];
};

type Props = {
    test: Test;
    question: CodingQuestion;
    difficulties: SelectOption[];
    languages: SelectOption[];
};

const backLinkClass =
    'mb-5 inline-flex h-10 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-950 px-4 text-sm font-semibold text-zinc-300 transition hover:border-emerald-500 hover:text-emerald-300';

export default function Edit({
    test,
    question,
    difficulties,
    languages,
}: Props) {
    const initialData: CodingQuestionFormData = {
        body: question.body,
        marks: String(question.marks),
        order: String(question.order),
        difficulty: question.difficulty,
        time_limit_minutes: formatMillisecondsAsMinutes(
            question.time_limit_ms,
        ),
        supported_languages: question.supported_languages,
        starter_code: Object.fromEntries(
            Object.entries(question.starter_code).map(([language, code]) => [
                language,
                code ?? '',
            ]),
        ),
        test_cases: question.test_cases.map((testCase) => ({
            input: testCase.input ?? '',
            expected_output: testCase.expected_output,
            is_hidden: testCase.is_hidden,
            points:
                testCase.points === null || testCase.points === undefined
                    ? ''
                    : String(testCase.points),
        })),
    };

    return (
        <AuthenticatedLayout
            theme="dark"
            header={
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                        Question Bank
                    </p>
                    <h2 className="mt-2 text-xl font-semibold leading-tight text-white">
                        Edit Coding Question
                    </h2>
                </div>
            }
        >
            <Head title="Edit Coding Question" />

            <div className="bg-zinc-950 px-4 py-10 text-zinc-100 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-5xl">
                    <Link
                        href={route('admin.tests.questions.index', test.id)}
                        className={backLinkClass}
                    >
                        Back to questions
                    </Link>

                    <CodingQuestionForm
                        test={test}
                        difficulties={difficulties}
                        languages={languages}
                        initialData={initialData}
                        submitLabel="Update question"
                        submitRoute={route(
                            'admin.tests.coding-questions.update',
                            [test.id, question.id],
                        )}
                        method="patch"
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function formatMillisecondsAsMinutes(value: number): string {
    const minutes = value / 60_000;

    return Number(minutes.toFixed(4)).toString();
}
