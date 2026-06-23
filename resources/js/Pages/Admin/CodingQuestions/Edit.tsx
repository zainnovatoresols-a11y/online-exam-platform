import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
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
        time_limit_ms: String(question.time_limit_ms),
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
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Edit Coding Question
                </h2>
            }
        >
            <Head title="Edit Coding Question" />

            <div className="py-12">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
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
