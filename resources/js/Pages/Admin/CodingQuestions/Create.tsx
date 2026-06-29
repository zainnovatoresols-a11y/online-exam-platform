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

type Props = {
    test: Test;
    difficulties: SelectOption[];
    languages: SelectOption[];
    next_order: number;
};

const backLinkClass =
    'mb-5 inline-flex h-10 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-950 px-4 text-sm font-semibold text-zinc-300 transition hover:border-emerald-500 hover:text-emerald-300';

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
        time_limit_minutes: '1',
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
            theme="dark"
            header={
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                        Question Bank
                    </p>
                    <h2 className="mt-2 text-xl font-semibold leading-tight text-white">
                        Create Coding Question
                    </h2>
                </div>
            }
        >
            <Head title="Create Coding Question" />

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
