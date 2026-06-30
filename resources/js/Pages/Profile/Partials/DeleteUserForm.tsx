import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef, useState } from 'react';

const labelClass = 'text-zinc-300';
const fieldClass =
    '!h-11 !rounded-xl !border-zinc-700 !bg-zinc-950 !text-zinc-100 !shadow-none outline-none transition placeholder:!text-zinc-600 focus:!border-red-500 focus:!ring-2 focus:!ring-red-500/30';
const dangerButtonClass =
    'inline-flex h-11 min-w-40 items-center justify-center rounded-xl border border-red-500/20 bg-red-500/15 px-5 text-sm font-bold text-red-200 transition hover:bg-red-500/25 focus:outline-none focus:ring-2 focus:ring-red-500/40 focus:ring-offset-2 focus:ring-offset-zinc-950 active:bg-red-500/20 disabled:opacity-60';
const secondaryButtonClass =
    'inline-flex h-11 min-w-28 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-950 px-5 text-sm font-bold text-zinc-300 transition hover:border-zinc-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-zinc-500/40 focus:ring-offset-2 focus:ring-offset-zinc-950 disabled:opacity-60';

export default function DeleteUserForm({
    className = '',
}: {
    className?: string;
}) {
    const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);
    const passwordInput = useRef<HTMLInputElement>(null);

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm({
        password: '',
    });

    const confirmUserDeletion = () => {
        setConfirmingUserDeletion(true);
    };

    const deleteUser: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingUserDeletion(false);

        clearErrors();
        reset();
    };

    return (
        <section className={`space-y-6 ${className}`}>
            <header>
                <h2 className="text-lg font-semibold text-white">
                    Delete Account
                </h2>

                <p className="mt-1 text-sm text-zinc-400">
                    Once your account is deleted, all of its resources and data
                    will be permanently deleted. Before deleting your account,
                    please download any data or information that you wish to
                    retain.
                </p>
            </header>

            <button
                type="button"
                onClick={confirmUserDeletion}
                className={dangerButtonClass}
            >
                Delete Account
            </button>

            <Modal
                show={confirmingUserDeletion}
                onClose={closeModal}
                panelClassName="mb-6 w-full transform overflow-hidden rounded-[18px] border border-zinc-800 bg-zinc-900 shadow-2xl shadow-black/30 transition-all sm:mx-auto sm:max-w-lg"
                backdropClassName="bg-zinc-950/80"
            >
                <form
                    onSubmit={deleteUser}
                    className="p-6 text-zinc-100"
                >
                    <h2 className="text-lg font-semibold text-white">
                        Are you sure you want to delete your account?
                    </h2>

                    <p className="mt-1 text-sm text-zinc-400">
                        Once your account is deleted, all of its resources and
                        data will be permanently deleted. Please enter your
                        password to confirm you would like to permanently delete
                        your account.
                    </p>

                    <div className="mt-6">
                        <InputLabel
                            htmlFor="password"
                            value="Password"
                            className={`sr-only ${labelClass}`}
                        />

                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            ref={passwordInput}
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            className={`mt-1 block w-full ${fieldClass}`}
                            isFocused
                            placeholder="Password"
                        />

                        <InputError
                            message={errors.password}
                            className="mt-2"
                        />
                    </div>

                    <div className="mt-6 flex flex-wrap justify-end gap-3">
                        <button
                            type="button"
                            onClick={closeModal}
                            className={secondaryButtonClass}
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            className={dangerButtonClass}
                            disabled={processing}
                        >
                            Delete Account
                        </button>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
