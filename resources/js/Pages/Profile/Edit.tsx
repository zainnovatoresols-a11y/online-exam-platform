import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({
    mustVerifyEmail,
    status,
}: PageProps<{ mustVerifyEmail: boolean; status?: string }>) {
    return (
        <AuthenticatedLayout
            theme="dark"
            header={
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                        Account Settings
                    </p>
                    <h2 className="mt-2 text-xl font-semibold leading-tight text-white">
                        Profile
                    </h2>
                </div>
            }
        >
            <Head title="Profile" />

            <div className="bg-zinc-950 px-4 py-10 text-zinc-100 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <div className="rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/20 sm:p-8">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="max-w-xl"
                        />
                    </div>

                    <div className="rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/20 sm:p-8">
                        <UpdatePasswordForm className="max-w-xl" />
                    </div>

                    <div className="rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/20 sm:p-8">
                        <DeleteUserForm className="max-w-xl" />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
