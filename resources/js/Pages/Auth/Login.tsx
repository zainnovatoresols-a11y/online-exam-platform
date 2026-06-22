import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PublicEntryLayout from '@/Layouts/PublicEntryLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const accessNotes = [
    {
        title: 'Role-aware workspace',
        description:
            'Super admins, organization admins, solo admins, and candidates are redirected into the right area after sign-in.',
    },
    {
        title: 'Controlled candidate entry',
        description:
            'Candidates continue through invitation and public test flows instead of open account registration.',
    },
    {
        title: 'Review-ready operations',
        description:
            'Admins can manage tests, invitations, attempts, proctoring evidence, exports, and review decisions.',
    },
];

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title="Log in" />

            <PublicEntryLayout
                eyebrow="Admin Access"
                title="Sign in to continue managing assessments."
                description="Use your workspace account to create tests, invite candidates, review results, and inspect proctoring evidence from the correct role-based dashboard."
                supportingContent={
                    <div className="space-y-5">
                        <div className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                            <p className="text-sm font-semibold text-zinc-950">
                                Secure access model
                            </p>
                            <p className="mt-2 text-sm leading-6 text-zinc-600">
                                Admin onboarding is separate from candidate
                                access, so operational users and test takers
                                do not enter through the same signup path.
                            </p>
                        </div>

                        <div className="space-y-4">
                            {accessNotes.map((note, index) => (
                                <div
                                    key={note.title}
                                    className="flex gap-4 rounded-lg border border-zinc-200 bg-white p-4 shadow-sm"
                                >
                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-900 text-sm font-semibold text-white">
                                        {index + 1}
                                    </div>
                                    <div>
                                        <h2 className="text-sm font-semibold text-zinc-950">
                                            {note.title}
                                        </h2>
                                        <p className="mt-1 text-sm leading-6 text-zinc-600">
                                            {note.description}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                }
            >
                <div className="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
                    <div className="border-b border-zinc-200 pb-6">
                        <p className="text-sm font-semibold text-zinc-500">
                            Workspace login
                        </p>
                        <h2 className="mt-3 text-2xl font-semibold text-zinc-950">
                            Welcome back
                        </h2>
                        <p className="mt-2 text-sm leading-6 text-zinc-600">
                            Enter your email and password to open your assigned
                            dashboard.
                        </p>
                    </div>

                    {status && (
                        <div className="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit} className="mt-6 space-y-5">
                        <div>
                            <InputLabel htmlFor="email" value="Email" />

                            <TextInput
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                className="mt-1 block h-11 w-full rounded-lg border-zinc-300 text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900"
                                autoComplete="username"
                                isFocused={true}
                                onChange={(event) =>
                                    setData('email', event.target.value)
                                }
                                required
                            />

                            <InputError message={errors.email} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="password" value="Password" />

                            <TextInput
                                id="password"
                                type="password"
                                name="password"
                                value={data.password}
                                className="mt-1 block h-11 w-full rounded-lg border-zinc-300 text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900"
                                autoComplete="current-password"
                                onChange={(event) =>
                                    setData('password', event.target.value)
                                }
                                required
                            />

                            <InputError
                                message={errors.password}
                                className="mt-2"
                            />
                        </div>

                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label className="flex items-center">
                                <Checkbox
                                    name="remember"
                                    checked={data.remember}
                                    className="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                                    onChange={(event) =>
                                        setData(
                                            'remember',
                                            event.target.checked,
                                        )
                                    }
                                />
                                <span className="ms-2 text-sm text-zinc-600">
                                    Remember me
                                </span>
                            </label>

                            {canResetPassword && (
                                <Link
                                    href={route('password.request')}
                                    className="text-sm font-medium text-zinc-700 underline underline-offset-4 hover:text-zinc-950 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                >
                                    Forgot password?
                                </Link>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex h-11 w-full items-center justify-center rounded-lg bg-zinc-900 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Log in
                        </button>
                    </form>
                </div>

                <div className="rounded-lg border border-zinc-200 bg-white p-5 text-sm text-zinc-600 shadow-sm">
                    <p className="font-semibold text-zinc-950">
                        Need admin access?
                    </p>
                    <p className="mt-2 leading-6">
                        Start with organization owner onboarding or create a
                        solo admin account for independent test operations.
                    </p>
                    <Link
                        href={route('onboarding.index')}
                        className="mt-4 inline-flex rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:border-zinc-400 hover:text-zinc-950 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                    >
                        Start onboarding
                    </Link>
                </div>
            </PublicEntryLayout>
        </>
    );
}
