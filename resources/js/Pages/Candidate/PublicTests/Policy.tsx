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

const primaryActionClassName =
    'w-full justify-center !rounded-xl !border-emerald-500 !bg-emerald-500 !px-5 !py-2.5 !text-black hover:!bg-emerald-400 focus:!ring-emerald-500 focus:!ring-offset-zinc-950 disabled:!opacity-50 sm:w-auto';

export default function Policy({
    test,
    email,
    invitation_token,
}: {
    test: Test;
    email: string;
    invitation_token: string | null;
}) {
    const { data, setData, post, processing } = useForm({
        email,
        invitation_token: invitation_token ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('candidate.public-tests.policy.accept', test.public_token));
    };

    return (
        <GuestLayout theme="dark">
            <Head title="Test Policy" />

            <form onSubmit={submit} className="space-y-6">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-300">
                        Test policy
                    </p>
                    <h1 className="mt-3 text-2xl font-semibold text-white">
                        {test.title}
                    </h1>
                    <p className="mt-3 text-sm text-zinc-400">
                        From:{' '}
                        {test.organization?.name ??
                            test.creator?.name ??
                            'Exam Admin'}
                    </p>
                </div>

                <dl className="grid gap-3 text-sm sm:grid-cols-2">
                    <PolicyDetail
                        label="Duration"
                        value={`${test.duration_minutes} minutes`}
                    />
                    <PolicyDetail
                        label="Pass mark"
                        value={String(test.pass_mark)}
                    />
                </dl>

                <div className="rounded-xl border border-zinc-800 bg-zinc-950/70 p-4">
                    <h2 className="text-base font-semibold text-white">
                        Guidelines
                    </h2>
                    <ul className="mt-4 list-disc space-y-2 pl-5 text-sm leading-6 text-zinc-300">
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
                <input
                    type="hidden"
                    value={data.invitation_token}
                    onChange={(event) =>
                        setData('invitation_token', event.target.value)
                    }
                />

                <PrimaryButton
                    disabled={processing}
                    className={primaryActionClassName}
                >
                    Accept guidelines
                </PrimaryButton>
            </form>
        </GuestLayout>
    );
}

function PolicyDetail({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border border-zinc-800 bg-zinc-950/70 p-4">
            <dt className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                {label}
            </dt>
            <dd className="mt-2 font-medium text-zinc-100">{value}</dd>
        </div>
    );
}
