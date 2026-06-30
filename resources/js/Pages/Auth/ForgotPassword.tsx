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

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout theme="dark">
            <Head title="Forgot Password" />

            <div className="space-y-6">
                <header>
                    <p className="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-300">
                        Account recovery
                    </p>
                    <h1 className="mt-3 text-2xl font-semibold text-white">
                        Forgot password
                    </h1>
                    <p className="mt-3 text-sm leading-6 text-zinc-400">
                        Enter your email address and we will send you a password
                        reset link so you can choose a new password.
                    </p>
                </header>

                {status && (
                    <div className="rounded-xl border border-emerald-400/20 bg-emerald-400/10 p-4 text-sm font-medium text-emerald-200">
                        {status}
                    </div>
                )}

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
                            isFocused={true}
                            onChange={(e) => setData('email', e.target.value)}
                        />

                        <InputError
                            message={errors.email}
                            className={errorClassName}
                        />
                    </div>

                    <div className="flex items-center justify-end">
                        <button
                            type="submit"
                            className={primaryButtonClassName}
                            disabled={processing}
                        >
                            Email Password Reset Link
                        </button>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
