import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef, useState } from 'react';

const labelClass = 'text-zinc-300';
const fieldClass =
    '!rounded-xl !border-zinc-700 !bg-zinc-950 !text-zinc-100 !shadow-none outline-none transition placeholder:!text-zinc-600 focus:!border-red-500 focus:!ring-2 focus:!ring-red-500/30';
const dangerButtonClass =
    '!h-11 !min-w-40 !justify-center !rounded-xl !border !border-red-500/20 !bg-red-500/15 !px-5 !py-0 !text-sm !font-bold !tracking-normal !text-red-200 hover:!bg-red-500/25 focus:!ring-red-500/40 focus:!ring-offset-zinc-950 active:!bg-red-500/20 disabled:!opacity-60';
const secondaryButtonClass =
    '!h-11 !min-w-28 !justify-center !rounded-xl !border-zinc-700 !bg-zinc-950 !px-5 !py-0 !text-sm !font-bold !tracking-normal !text-zinc-300 hover:!border-zinc-600 hover:!bg-zinc-950 hover:!text-white focus:!ring-zinc-500/40 focus:!ring-offset-zinc-950';

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

            <DangerButton
                onClick={confirmUserDeletion}
                className={dangerButtonClass}
            >
                Delete Account
            </DangerButton>

            <Modal show={confirmingUserDeletion} onClose={closeModal}>
                <form
                    onSubmit={deleteUser}
                    className="border border-zinc-800 bg-zinc-900 p-6 text-zinc-100"
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
                        <SecondaryButton
                            onClick={closeModal}
                            className={secondaryButtonClass}
                        >
                            Cancel
                        </SecondaryButton>

                        <DangerButton
                            className={dangerButtonClass}
                            disabled={processing}
                        >
                            Delete Account
                        </DangerButton>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
