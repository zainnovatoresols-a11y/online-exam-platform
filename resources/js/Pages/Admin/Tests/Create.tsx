import InputError from '@/Components/InputError';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const defaultPolicy = `Read all instructions carefully before starting the test.
Do not copy, paste, or use outside help during the test.
Do not switch tabs, open another browser, or use another device for answers.
Answer every question yourself and submit before the timer ends.
Once the test starts, the timer cannot be paused.`;

type CandidateField = 'phone' | 'stack_name';

const fieldLabelClass = 'block text-sm font-semibold text-zinc-200';
const fieldHelpClass = 'mt-1 text-sm leading-relaxed text-zinc-500';
const fieldControlClass =
    'mt-2 block w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2.5 text-sm text-zinc-100 shadow-sm transition placeholder:text-zinc-600 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30';
const panelClass = 'rounded-2xl border border-zinc-800 bg-zinc-950/70 p-5';
const checkboxClass =
    'mt-1 rounded border-zinc-600 bg-zinc-950 text-emerald-500 focus:ring-2 focus:ring-emerald-400/40 focus:ring-offset-0';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        duration_minutes: 60,
        pass_mark: 50,
        starts_at: '',
        public_access_enabled: false,
        candidate_fields: ['phone'] as CandidateField[],
        policy_text: defaultPolicy,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('admin.tests.store'));
    };

    const toggleCandidateField = (field: CandidateField) => {
        if (data.candidate_fields.includes(field)) {
            setData(
                'candidate_fields',
                data.candidate_fields.filter((value) => value !== field),
            );
            return;
        }

        setData('candidate_fields', [...data.candidate_fields, field]);
    };

    return (
        <AuthenticatedLayout
            theme="dark"
            header={
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                        Assessment Setup
                    </p>
                    <h2 className="mt-2 text-xl font-semibold leading-tight text-white">
                        Create Test
                    </h2>
                </div>
            }
        >
            <Head title="Create Test" />

            <div className="bg-zinc-950 px-4 py-10 text-zinc-100 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-4xl">
                    <form
                        onSubmit={submit}
                        className="space-y-7 rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/20 sm:p-8"
                    >
                        <div className="flex flex-col gap-4 border-b border-zinc-800 pb-6 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">
                                    Test details
                                </p>
                                <h1 className="mt-2 text-2xl font-bold text-white">
                                    Build a new assessment
                                </h1>
                                <p className="mt-2 max-w-2xl text-sm leading-relaxed text-zinc-500">
                                    Set the timing, access rules, candidate
                                    requirements, and policy candidates see
                                    before starting.
                                </p>
                            </div>

                            <Link
                                href={route('admin.tests.index')}
                                className="inline-flex h-10 items-center justify-center rounded-xl border border-zinc-700 px-4 text-sm font-semibold text-zinc-300 transition hover:border-zinc-600 hover:text-white"
                            >
                                Back to tests
                            </Link>
                        </div>

                        <div>
                            <label htmlFor="title" className={fieldLabelClass}>
                                Title
                            </label>
                            <input
                                id="title"
                                className={fieldControlClass}
                                value={data.title}
                                onChange={(event) =>
                                    setData('title', event.target.value)
                                }
                                required
                            />
                            <InputError message={errors.title} className="mt-2" />
                        </div>

                        <div>
                            <label
                                htmlFor="description"
                                className={fieldLabelClass}
                            >
                                Description
                            </label>
                            <textarea
                                id="description"
                                className={fieldControlClass}
                                rows={4}
                                value={data.description}
                                onChange={(event) =>
                                    setData('description', event.target.value)
                                }
                            />
                            <InputError
                                message={errors.description}
                                className="mt-2"
                            />
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label
                                    htmlFor="duration_minutes"
                                    className={fieldLabelClass}
                                >
                                    Duration minutes
                                </label>
                                <input
                                    id="duration_minutes"
                                    type="number"
                                    min="1"
                                    className={fieldControlClass}
                                    value={data.duration_minutes}
                                    onChange={(event) =>
                                        setData(
                                            'duration_minutes',
                                            Number(event.target.value),
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.duration_minutes}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <label
                                    htmlFor="pass_mark"
                                    className={fieldLabelClass}
                                >
                                    Pass mark
                                </label>
                                <input
                                    id="pass_mark"
                                    type="number"
                                    min="1"
                                    className={fieldControlClass}
                                    value={data.pass_mark}
                                    onChange={(event) =>
                                        setData(
                                            'pass_mark',
                                            Number(event.target.value),
                                        )
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.pass_mark}
                                    className="mt-2"
                                />
                            </div>
                        </div>

                        <div>
                            <label
                                htmlFor="starts_at"
                                className={fieldLabelClass}
                            >
                                Start time
                            </label>
                            <input
                                id="starts_at"
                                type="datetime-local"
                                className={fieldControlClass}
                                value={data.starts_at}
                                onChange={(event) =>
                                    setData('starts_at', event.target.value)
                                }
                            />
                            <InputError
                                message={errors.starts_at}
                                className="mt-2"
                            />
                        </div>

                        <div className={panelClass}>
                            <div className="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    className={checkboxClass}
                                    checked={data.public_access_enabled}
                                    onChange={(event) =>
                                        setData(
                                            'public_access_enabled',
                                            event.currentTarget.checked,
                                        )
                                    }
                                />
                                <div>
                                    <p className={fieldLabelClass}>
                                        Allow anyone with the public URL
                                    </p>
                                    <p className={fieldHelpClass}>
                                        When this is off, only emailed/invited
                                        addresses can register through the
                                        public test link.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className={panelClass}>
                            <p className={fieldLabelClass}>
                                Required candidate fields
                            </p>
                            <p className={fieldHelpClass}>
                                Name and email are always required.
                            </p>
                            <div className="mt-4 space-y-3">
                                <label className="flex items-center gap-3 text-sm font-medium text-zinc-300">
                                    <input
                                        type="checkbox"
                                        className={checkboxClass}
                                        checked={data.candidate_fields.includes(
                                            'phone',
                                        )}
                                        onChange={() =>
                                            toggleCandidateField('phone')
                                        }
                                    />
                                    Phone
                                </label>
                                <label className="flex items-center gap-3 text-sm font-medium text-zinc-300">
                                    <input
                                        type="checkbox"
                                        className={checkboxClass}
                                        checked={data.candidate_fields.includes(
                                            'stack_name',
                                        )}
                                        onChange={() =>
                                            toggleCandidateField('stack_name')
                                        }
                                    />
                                    Stack / Skill
                                </label>
                            </div>
                            <InputError
                                message={errors.candidate_fields}
                                className="mt-2"
                            />
                        </div>

                        <div>
                            <label
                                htmlFor="policy_text"
                                className={fieldLabelClass}
                            >
                                Candidate policy and guidelines
                            </label>
                            <textarea
                                id="policy_text"
                                className={fieldControlClass}
                                rows={7}
                                value={data.policy_text}
                                onChange={(event) =>
                                    setData('policy_text', event.target.value)
                                }
                            />
                            <InputError
                                message={errors.policy_text}
                                className="mt-2"
                            />
                        </div>

                        <div className="flex items-center gap-4">
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex h-11 items-center justify-center rounded-xl bg-emerald-500 px-5 text-sm font-bold text-black transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40 disabled:opacity-50"
                            >
                                Save
                            </button>
                            <Link
                                href={route('admin.tests.index')}
                                className="text-sm font-semibold text-zinc-400 underline transition hover:text-white"
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
