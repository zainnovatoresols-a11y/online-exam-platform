import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PublicAssessmentLayout from '@/Layouts/PublicAssessmentLayout';
import { Head, Link } from '@inertiajs/react';
import { PropsWithChildren, ReactNode } from 'react';

type Attempt = {
    id: number;
    access_token?: string | null;
    is_public?: boolean;
    status: string;
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

export default function Result({
    attempt,
    test,
}: {
    attempt: Attempt;
    test: Test;
}) {
    return (
        <AssessmentLayout
            isPublic={Boolean(attempt.is_public)}
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Assessment Submitted
                </h2>
            }
        >
            <Head title={`${test.title} Submitted`} />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <p className="text-sm font-medium uppercase text-gray-500">
                            Assessment submitted
                        </p>
                        <h1 className="mt-2 text-2xl font-semibold text-gray-900">
                            Thank you for completing the assessment.
                        </h1>

                        <div className="mt-6 rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                            <p>
                                Your answers have been received for{' '}
                                <span className="font-medium text-gray-900">
                                    {test.title}
                                </span>
                                .
                            </p>
                            <p className="mt-2">
                                If your profile meets our requirements, our HR
                                team will contact you for the next step.
                            </p>
                        </div>

                        {attempt.submitted_at && (
                            <p className="mt-4 text-sm text-gray-600">
                                Submitted on{' '}
                                {formatDateTime(attempt.submitted_at)}
                            </p>
                        )}

                        {!attempt.is_public && (
                            <Link
                                href={route('candidate.dashboard')}
                                className="mt-6 inline-flex rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700"
                            >
                                Back to dashboard
                            </Link>
                        )}
                    </div>
                </div>
            </div>
        </AssessmentLayout>
    );
}

function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
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
