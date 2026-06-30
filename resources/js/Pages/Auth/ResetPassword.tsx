import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const labelClassName = '!text-zinc-300';
const inputClassName =
    'mt-1 block h-11 w-full !rounded-xl !border-zinc-700 !bg-zinc-950 !text-zinc-100 !shadow-none outline-none transition placeholder:!text-zinc-600 focus:!border-emerald-500 focus:!ring-2 focus:!ring-emerald-500/30';
const errorClassName = 'mt-2 !text-red-300';
const primaryButtonClassName =
    'inline-flex h-11 w-full items-center justify-center rounded-xl bg-emerald-500 px-5 text-sm font-bold text-black transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:ring-offset-2 focus:ring-offset-zinc-950 disabled:opacity-60 sm:w-auto';

export default function ResetPassword({
    token,
    email,
}: {
    token: string;
    email: string;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout theme="dark">
            <Head title="Reset Password" />

            <div className="space-y-6">
                <header>
                    <p className="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-300">
                        Account recovery
                    </p>
                    <h1 className="mt-3 text-2xl font-semibold text-white">
                        Reset password
                    </h1>
                    <p className="mt-3 text-sm leading-6 text-zinc-400">
                        Choose a new password for your account.
                    </p>
                </header>

                <form onSubmit={submit} className="space-y-6">
                    <div>
                        <InputLabel
                            htmlFor="email"
                            value="Email"
                            className={labelClassName}
                        />

                        <TextInput
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            className={inputClassName}
                            autoComplete="username"
                            onChange={(e) => setData('email', e.target.value)}
                        />

                        <InputError
                            message={errors.email}
                            className={errorClassName}
                        />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="password"
                            value="Password"
                            className={labelClassName}
                        />

                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            className={inputClassName}
                            autoComplete="new-password"
                            isFocused={true}
                            onChange={(e) => setData('password', e.target.value)}
                        />

                        <InputError
                            message={errors.password}
                            className={errorClassName}
                        />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="password_confirmation"
                            value="Confirm Password"
                            className={labelClassName}
                        />

                        <TextInput
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            value={data.password_confirmation}
                            className={inputClassName}
                            autoComplete="new-password"
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                        />

                        <InputError
                            message={errors.password_confirmation}
                            className={errorClassName}
                        />
                    </div>

                    <div className="flex items-center justify-end">
                        <button
                            type="submit"
                            className={primaryButtonClassName}
                            disabled={processing}
                        >
                            Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
