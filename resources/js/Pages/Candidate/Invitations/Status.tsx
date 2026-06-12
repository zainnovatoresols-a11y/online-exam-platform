import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link } from '@inertiajs/react';

type Invitation = {
    email: string;
    test: {
        id: number;
        title: string;
    };
} | null;

type Props = {
    status: string;
    message: string;
    invitation: Invitation;
};

export default function Status({ status, message, invitation }: Props) {
    return (
        <GuestLayout>
            <Head title="Invitation Status" />

            <div className="space-y-4">
                <p className="text-sm font-medium uppercase text-gray-500">
                    {status}
                </p>
                <h1 className="text-xl font-semibold text-gray-900">
                    {message}
                </h1>

                {invitation && (
                    <p className="text-sm text-gray-600">
                        {invitation.test.title} was sent to {invitation.email}.
                    </p>
                )}

                <Link href={route('login')} className="text-sm text-gray-700 underline">
                    Go to login
                </Link>
            </div>
        </GuestLayout>
    );
}
