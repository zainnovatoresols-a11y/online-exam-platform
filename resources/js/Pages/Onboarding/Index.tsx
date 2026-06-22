import AccessPathCard from '@/Components/public/AccessPathCard';
import PublicEntryLayout from '@/Layouts/PublicEntryLayout';
import { Head, Link } from '@inertiajs/react';

const onboardingNotes = [
    'Admin onboarding only. Candidate access is created from invitations and public test registration after a test is published.',
    'Organization owners can create their workspace first, then add admins inside that organization.',
    'Solo admins skip organization setup and start directly with standalone test operations.',
];

export default function Index() {
    return (
        <>
            <Head title="Admin Onboarding" />

            <PublicEntryLayout
                eyebrow="Admin Onboarding"
                title="Choose the admin setup that matches your assessment operations."
                description="Both paths lead into the same testing platform, but they start with different ownership models. Pick the one that fits how your team will create tests and manage results."
                rightSectionClassName="space-y-4"
                supportingContent={
                    <div className="space-y-6">
                        <div className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                            <p className="text-sm font-semibold text-zinc-950">
                                Candidate access remains controlled
                            </p>
                            <p className="mt-2 text-sm leading-6 text-zinc-600">
                                There is no open candidate signup here. Admins
                                publish tests first, then candidates enter
                                through invitation flows or public assessment
                                links tied to a specific test.
                            </p>
                        </div>

                        <div className="space-y-4">
                            {onboardingNotes.map((note, index) => (
                                <div key={note} className="flex gap-4">
                                    <div className="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-zinc-300 bg-white text-sm font-semibold text-zinc-700">
                                        {index + 1}
                                    </div>
                                    <p className="text-sm leading-6 text-zinc-600">
                                        {note}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                }
            >
                <AccessPathCard
                    eyebrow="Shared Ownership"
                    title="Organization owner"
                    description="Create the organization workspace and land in the owner dashboard with access to manage your own organization and add admins."
                    href={route('onboarding.organization-owner.create')}
                    cta="Continue as organization owner"
                    helper="Recommended when multiple admins will work under one organization."
                    highlights={[
                        'Creates the organization and owner account together',
                        'Lets you add admins after setup',
                        'Keeps all organization tests under one owner structure',
                    ]}
                    variant="primary"
                />

                <AccessPathCard
                    eyebrow="Standalone Access"
                    title="Solo admin"
                    description="Open a direct admin account for building and reviewing tests independently, without organization setup."
                    href={route('onboarding.solo-admin.create')}
                    cta="Continue as solo admin"
                    helper="Best when one admin owns the full test workflow."
                    highlights={[
                        'No organization record required',
                        'Straight into solo test creation',
                        'Simple path for independent assessment operations',
                    ]}
                />

                <div className="flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-5 py-4 text-sm text-zinc-600 shadow-sm">
                    <span>Already have an account?</span>
                    <Link
                        href={route('login')}
                        className="font-semibold text-zinc-950 underline underline-offset-4"
                    >
                        Log in
                    </Link>
                </div>
            </PublicEntryLayout>
        </>
    );
}
