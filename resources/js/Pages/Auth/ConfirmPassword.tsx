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

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout theme="dark">
            <Head title="Confirm Password" />

            <div className="space-y-6">
                <header>
                    <p className="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-300">
                        Secure area
                    </p>
                    <h1 className="mt-3 text-2xl font-semibold text-white">
                        Confirm password
                    </h1>
                    <p className="mt-3 text-sm leading-6 text-zinc-400">
                        This is a secure area of the application. Please confirm
                        your password before continuing.
                    </p>
                </header>

                <form onSubmit={submit} className="space-y-6">
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
                            isFocused={true}
                            onChange={(e) => setData('password', e.target.value)}
                        />

                        <InputError
                            message={errors.password}
                            className={errorClassName}
                        />
                    </div>

                    <div className="flex items-center justify-end">
                        <button
                            type="submit"
                            className={primaryButtonClassName}
                            disabled={processing}
                        >
                            Confirm
                        </button>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
