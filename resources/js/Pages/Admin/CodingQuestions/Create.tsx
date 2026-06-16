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

type Props = {
    test: Test;
    difficulties: SelectOption[];
    languages: SelectOption[];
    next_order: number;
};

export default function Create({
    test,
    difficulties,
    languages,
    next_order,
}: Props) {
    const initialData: CodingQuestionFormData = {
        body: '',
        marks: '1',
        order: String(next_order),
        difficulty: 'easy',
        time_limit_ms: '2000',
        memory_limit_kb: '128000',
        supported_languages: ['php'],
        starter_code: {
            php: '',
        },
        test_cases: [
            {
                input: '',
                expected_output: '',
                is_hidden: false,
                points: '',
            },
        ],
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Create Coding Question
                </h2>
            }
        >
            <Head title="Create Coding Question" />

            <div className="py-12">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    <CodingQuestionForm
                        test={test}
                        difficulties={difficulties}
                        languages={languages}
                        initialData={initialData}
                        submitLabel="Save question"
                        submitRoute={route(
                            'admin.tests.coding-questions.store',
                            test.id,
                        )}
                        method="post"
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
