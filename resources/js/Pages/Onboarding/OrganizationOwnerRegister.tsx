import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import QuizPlatformLogo from '@/Components/QuizPlatformLogo';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

// ─── Data ────────────────────────────────────────────────────────────────────

const benefits = [
    'Creates the organization record and owner account in one step.',
    'Lets you manage your own organization details after sign-in.',
    'Supports adding more admins inside the same organization later.',
];

// ─── Component ───────────────────────────────────────────────────────────────

export default function OrganizationOwnerRegister() {
    const { data, setData, post, processing, errors, reset } = useForm({
        organization_name: '',
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('onboarding.organization-owner.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Organization Owner Onboarding" />

            <div
                className="min-h-screen bg-zinc-950 text-zinc-100"
                style={{ fontFamily: "-apple-system, BlinkMacSystemFont, 'Inter', sans-serif" }}
            >
                {/* ── Sticky nav ──────────────────────────────────────────── */}
                <nav
                    className="sticky top-0 z-50 flex h-14 items-center justify-between border-b border-white/5 px-4 sm:px-8 backdrop-blur-md"
                    style={{ background: 'rgba(9,9,11,0.85)' }}
                >
                    <Link href="/" className="flex items-center gap-2.5">
                        <QuizPlatformLogo markClassName="h-7 w-7 rounded-lg" />
                    </Link>

                    <div className="flex items-center gap-2 sm:gap-3">
                        <span className="hidden text-xs text-zinc-500 sm:inline">Already have an account?</span>
                        <Link
                            href={route('login')}
                            className="rounded-lg bg-emerald-500 px-3.5 py-1.5 text-xs font-bold text-black hover:bg-emerald-400 transition-colors"
                        >
                            Log in
                        </Link>
                    </div>
                </nav>

                {/* ── Header ──────────────────────────────────────────────── */}
                <section className="relative overflow-hidden px-4 pb-8 pt-12 text-center sm:px-8 sm:pb-10 sm:pt-14">
                    <div
                        className="pointer-events-none absolute left-1/2 top-[-40px] h-[280px] w-[520px] -translate-x-1/2"
                        style={{ background: 'radial-gradient(circle, rgba(16,185,129,0.12) 0%, transparent 70%)' }}
                    />

                    <div className="relative">
                        <div
                            className="mb-5 inline-flex items-center gap-2 rounded-full border px-3.5 py-1"
                            style={{ borderColor: 'rgba(16,185,129,0.2)', background: 'rgba(16,185,129,0.06)' }}
                        >
                            <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400" />
                            <span className="text-[10px] font-semibold uppercase tracking-widest text-emerald-400">
                                Organization Setup
                            </span>
                        </div>

                        <h1 className="mx-auto mb-3 max-w-2xl text-2xl font-extrabold leading-[1.18] tracking-tight text-white sm:text-3xl lg:text-4xl">
                            Create your organization workspace and owner account.
                        </h1>

                        <p className="mx-auto max-w-md text-sm leading-relaxed text-zinc-500">
                            Use this path when your exam operations belong to a shared organization and more admins may need access after setup.
                        </p>
                    </div>
                </section>

                {/* ── Benefits strip ──────────────────────────────────────── */}
                <div
                    className="grid grid-cols-1 sm:grid-cols-3"
                    style={{
                        background: 'rgba(24,24,27,0.3)',
                        borderTop: '1px solid rgba(39,39,42,0.5)',
                        borderBottom: '1px solid rgba(39,39,42,0.5)',
                    }}
                >
                    {benefits.map((benefit, i) => (
                        <div
                            key={benefit}
                            className="px-5 py-5 text-center sm:py-6"
                            style={
                                i < benefits.length - 1
                                    ? { borderRight: '1px solid rgba(39,39,42,0.5)' }
                                    : {}
                            }
                        >
                            <div className="mx-auto mb-2.5 flex h-7 w-7 items-center justify-center rounded-full border border-zinc-700 text-xs font-bold text-emerald-400">
                                {i + 1}
                            </div>
                            <p className="text-[11px] leading-relaxed text-zinc-500">{benefit}</p>
                        </div>
                    ))}
                </div>

                {/* ── Form ────────────────────────────────────────────────── */}
                <div className="mx-auto max-w-2xl px-4 py-10 sm:px-6 sm:py-12">

                    {/* What happens callout */}
                    <div className="mb-5 rounded-xl border border-zinc-800 bg-zinc-900 px-5 py-4 sm:p-5">
                        <p className="text-sm font-semibold text-white">
                            What happens after signup
                        </p>
                        <p className="mt-1.5 text-xs leading-relaxed text-zinc-500">
                            You will land on the organization owner dashboard, where you can review your workspace and add admins for your team.
                        </p>
                    </div>

                    {/* Form card */}
                    <div className="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900">

                        {/* Card header */}
                        <div className="border-b border-zinc-800 px-6 py-5 sm:px-8 sm:py-6">
                            <p className="text-[10px] font-semibold uppercase tracking-widest text-zinc-600">
                                Owner access
                            </p>
                            <h2 className="mt-1.5 text-lg font-bold text-white sm:text-xl">
                                Create your organization owner account
                            </h2>
                            <p className="mt-1.5 text-xs leading-relaxed text-zinc-500">
                                This step sets the organization name and the first owner login for that workspace.
                            </p>
                        </div>

                        {/* Card body */}
                        <div className="px-6 py-6 sm:px-8 sm:py-7">
                            <form onSubmit={submit} className="space-y-6">

                                {/* ── Organization details ─────────────────── */}
                                <div className="space-y-4">
                                    <div>
                                        <h3 className="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">
                                            Organization details
                                        </h3>
                                        <p className="mt-1 text-xs text-zinc-500">
                                            Name the workspace your admins will manage.
                                        </p>
                                    </div>

                                    <div>
                                        <InputLabel
                                            htmlFor="organization_name"
                                            value="Organization name"
                                            className="mb-1.5 block text-xs font-medium text-zinc-300"
                                        />
                                        <TextInput
                                            id="organization_name"
                                            value={data.organization_name}
                                            className="block h-11 w-full rounded-lg border-zinc-700 bg-zinc-800 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                            placeholder="Acme Institute"
                                            isFocused={true}
                                            onChange={(event) =>
                                                setData('organization_name', event.target.value)
                                            }
                                            required
                                        />
                                        <InputError message={errors.organization_name} className="mt-1.5 text-xs" />
                                    </div>
                                </div>

                                {/* ── Owner account ────────────────────────── */}
                                <div className="space-y-4 border-t border-zinc-800 pt-6">
                                    <div>
                                        <h3 className="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">
                                            Owner account
                                        </h3>
                                        <p className="mt-1 text-xs text-zinc-500">
                                            This login will become the first super admin for the organization.
                                        </p>
                                    </div>

                                    <div>
                                        <InputLabel
                                            htmlFor="name"
                                            value="Your name"
                                            className="mb-1.5 block text-xs font-medium text-zinc-300"
                                        />
                                        <TextInput
                                            id="name"
                                            value={data.name}
                                            className="block h-11 w-full rounded-lg border-zinc-700 bg-zinc-800 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                            autoComplete="name"
                                            placeholder="Ayesha Khan"
                                            onChange={(event) => setData('name', event.target.value)}
                                            required
                                        />
                                        <InputError message={errors.name} className="mt-1.5 text-xs" />
                                    </div>

                                    <div>
                                        <InputLabel
                                            htmlFor="email"
                                            value="Email address"
                                            className="mb-1.5 block text-xs font-medium text-zinc-300"
                                        />
                                        <TextInput
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            className="block h-11 w-full rounded-lg border-zinc-700 bg-zinc-800 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                            autoComplete="email"
                                            placeholder="owner@acme.com"
                                            onChange={(event) => setData('email', event.target.value)}
                                            required
                                        />
                                        <InputError message={errors.email} className="mt-1.5 text-xs" />
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <InputLabel
                                                htmlFor="password"
                                                value="Password"
                                                className="mb-1.5 block text-xs font-medium text-zinc-300"
                                            />
                                            <TextInput
                                                id="password"
                                                type="password"
                                                value={data.password}
                                                className="block h-11 w-full rounded-lg border-zinc-700 bg-zinc-800 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                                autoComplete="new-password"
                                                onChange={(event) => setData('password', event.target.value)}
                                                required
                                            />
                                            <InputError message={errors.password} className="mt-1.5 text-xs" />
                                        </div>

                                        <div>
                                            <InputLabel
                                                htmlFor="password_confirmation"
                                                value="Confirm password"
                                                className="mb-1.5 block text-xs font-medium text-zinc-300"
                                            />
                                            <TextInput
                                                id="password_confirmation"
                                                type="password"
                                                value={data.password_confirmation}
                                                className="block h-11 w-full rounded-lg border-zinc-700 bg-zinc-800 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                                autoComplete="new-password"
                                                onChange={(event) =>
                                                    setData('password_confirmation', event.target.value)
                                                }
                                                required
                                            />
                                            <InputError message={errors.password_confirmation} className="mt-1.5 text-xs" />
                                        </div>
                                    </div>
                                </div>

                                {/* ── Actions ──────────────────────────────── */}
                                <div className="flex flex-col gap-3 border-t border-zinc-800 pt-6 sm:flex-row sm:items-center sm:justify-between">
                                    <Link
                                        href={route('onboarding.index')}
                                        className="text-xs font-medium text-zinc-400 underline underline-offset-4 hover:text-zinc-200"
                                    >
                                        Back to account choices
                                    </Link>

                                    <PrimaryButton
                                        disabled={processing}
                                        className="inline-flex h-11 w-full items-center justify-center rounded-xl bg-emerald-500 px-6 text-sm font-bold text-black hover:bg-emerald-400 focus:bg-emerald-400 focus:ring-emerald-500 active:bg-emerald-500 disabled:opacity-60 sm:w-auto"
                                    >
                                        Create organization owner account
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {/* ── Footer ──────────────────────────────────────────────── */}
                <footer
                    className="flex items-center justify-between px-4 py-5 sm:px-8"
                    style={{ borderTop: '1px solid rgba(39,39,42,0.5)' }}
                >
                    <div className="flex items-center gap-2.5">
                        <QuizPlatformLogo markClassName="h-[26px] w-[26px] rounded-lg" />
                    </div>
                    <span className="text-[10px] text-zinc-700">
                        (c) 2025 Online Quiz Platform. All rights reserved.
                    </span>
                </footer>

            </div>
        </>
    );
}
