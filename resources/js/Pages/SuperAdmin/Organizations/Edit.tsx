import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Organization = {
    id: number;
    name: string;
};

const labelClass = 'text-zinc-300';
const fieldClass =
    '!rounded-xl !border-zinc-700 !bg-zinc-950 !text-zinc-100 !shadow-none outline-none transition placeholder:!text-zinc-600 focus:!border-emerald-500 focus:!ring-2 focus:!ring-emerald-500/30';
const formSectionClass =
    'space-y-7 rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/20';
const primaryButtonClass =
    '!h-11 !min-w-28 !justify-center !rounded-xl !bg-emerald-500 !px-5 !py-0 !text-sm !font-bold !tracking-normal !text-black hover:!bg-emerald-400 focus:!bg-emerald-400 focus:!ring-emerald-500/40 focus:!ring-offset-zinc-950 active:!bg-emerald-500 disabled:!opacity-60';
const secondaryLinkClass =
    'inline-flex h-11 min-w-32 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-950 px-5 text-sm font-bold text-zinc-300 transition hover:border-emerald-500 hover:text-emerald-300';

export default function Edit({ organization }: { organization: Organization }) {
    const { data, setData, patch, processing, errors } = useForm({
        name: organization.name,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        patch(route('super-admin.organizations.update', organization.id));
    };

    return (
        <AuthenticatedLayout
            theme="dark"
            header={
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                        Super Admin
                    </p>
                    <h2 className="mt-2 text-xl font-semibold leading-tight text-white">
                        Edit Organization
                    </h2>
                </div>
            }
        >
            <Head title="Edit Organization" />

            <div className="bg-zinc-950 px-4 py-10 text-zinc-100 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-3xl space-y-6">
                    <Link
                        href={route(
                            'super-admin.organizations.show',
                            organization.id,
                        )}
                        className={secondaryLinkClass}
                    >
                        Back to organization
                    </Link>

                    <form
                        onSubmit={submit}
                        className={formSectionClass}
                    >
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                                Organization
                            </p>
                            <h3 className="mt-2 text-2xl font-bold text-white">
                                {organization.name}
                            </h3>
                        </div>

                        <div className="space-y-2">
                            <InputLabel
                                htmlFor="name"
                                value="Name"
                                className={labelClass}
                            />
                            <TextInput
                                id="name"
                                className={`block w-full ${fieldClass}`}
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                required
                            />
                            <InputError message={errors.name} className="mt-2" />
                        </div>

                        <div className="flex flex-wrap items-center gap-4 pt-2">
                            <PrimaryButton
                                disabled={processing}
                                className={primaryButtonClass}
                            >
                                Update
                            </PrimaryButton>
                            <Link
                                href={route(
                                    'super-admin.organizations.show',
                                    organization.id,
                                )}
                                className={secondaryLinkClass}
                            >
                                Cancel
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
