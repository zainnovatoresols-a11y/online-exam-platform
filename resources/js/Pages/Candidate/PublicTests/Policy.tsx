import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Test = {
    title: string;
    description: string | null;
    duration_minutes: number;
    pass_mark: number;
    public_token: string;
    public_access_enabled: boolean;
    policy_text: string;
    organization: { id: number; name: string } | null;
    creator: { id: number; name: string; email: string } | null;
};

export default function Policy({ test, email }: { test: Test; email: string }) {
    const { data, setData, post, processing } = useForm({
        email,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('candidate.public-tests.policy.accept', test.public_token));
    };

    return (
        <GuestLayout>
            <Head title="Test Policy" />

            <form onSubmit={submit} className="space-y-6">
                <div>
                    <p className="text-sm font-medium uppercase text-gray-500">
                        Test policy
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold text-gray-900">
                        {test.title}
                    </h1>
                    <p className="mt-2 text-sm text-gray-600">
                        From:{' '}
                        {test.organization?.name ??
                            test.creator?.name ??
                            'Exam Admin'}
                    </p>
                </div>

                <dl className="grid gap-4 rounded-md bg-gray-50 p-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt className="font-medium text-gray-900">Duration</dt>
                        <dd className="mt-1 text-gray-600">
                            {test.duration_minutes} minutes
                        </dd>
                    </div>
                    <div>
                        <dt className="font-medium text-gray-900">Pass mark</dt>
                        <dd className="mt-1 text-gray-600">
                            {test.pass_mark}
                        </dd>
                    </div>
                </dl>

                <div className="rounded-md border border-gray-200 p-4">
                    <h2 className="text-base font-semibold text-gray-900">
                        Guidelines
                    </h2>
                    <ul className="mt-4 list-disc space-y-2 pl-5 text-sm text-gray-700">
                        {test.policy_text
                            .split('\n')
                            .filter((line) => line.trim() !== '')
                            .map((line) => (
                                <li key={line}>{line}</li>
                            ))}
                    </ul>
                </div>

                <input
                    type="hidden"
                    value={data.email}
                    onChange={(event) => setData('email', event.target.value)}
                />

                <PrimaryButton disabled={processing}>
                    Accept guidelines
                </PrimaryButton>
            </form>
        </GuestLayout>
    );
}
