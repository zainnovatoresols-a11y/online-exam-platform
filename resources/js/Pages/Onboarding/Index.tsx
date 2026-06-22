import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index() {
    return (
        <GuestLayout>
            <Head title="Get Started" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900">
                        Choose your account setup
                    </h1>
                    <p className="mt-2 text-sm text-gray-600">
                        Pick the onboarding path that matches how you manage
                        assessments.
                    </p>
                </div>

                <div className="space-y-4">
                    <div className="rounded-lg border border-gray-200 p-4">
                        <h2 className="text-lg font-semibold text-gray-900">
                            Organization owner
                        </h2>
                        <p className="mt-2 text-sm text-gray-600">
                            Create your organization account as a super admin,
                            then add admins inside your organization.
                        </p>
                        <Link
                            href={route(
                                'onboarding.organization-owner.create',
                            )}
                            className="mt-4 inline-flex rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white"
                        >
                            Continue as organization owner
                        </Link>
                    </div>

                    <div className="rounded-lg border border-gray-200 p-4">
                        <h2 className="text-lg font-semibold text-gray-900">
                            Solo admin
                        </h2>
                        <p className="mt-2 text-sm text-gray-600">
                            Create a standalone admin account to manage solo
                            tests without an organization.
                        </p>
                        <Link
                            href={route('onboarding.solo-admin.create')}
                            className="mt-4 inline-flex rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700"
                        >
                            Continue as solo admin
                        </Link>
                    </div>
                </div>

                <div className="space-y-2 border-t border-gray-200 pt-4 text-sm text-gray-600">
                    <p>
                        Taking an assessment instead?{' '}
                        <Link
                            href={route('register')}
                            className="font-medium text-gray-900 underline"
                        >
                            Register as candidate
                        </Link>
                        .
                    </p>
                    <p>
                        Already have an account?{' '}
                        <Link
                            href={route('login')}
                            className="font-medium text-gray-900 underline"
                        >
                            Log in
                        </Link>
                        .
                    </p>
                </div>
            </div>
        </GuestLayout>
    );
}
