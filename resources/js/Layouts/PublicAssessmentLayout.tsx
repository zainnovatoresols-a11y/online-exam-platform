import QuizPlatformLogo from '@/Components/QuizPlatformLogo';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode } from 'react';

export default function PublicAssessmentLayout({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const { flash } = usePage<PageProps>().props;

    return (
        <div className="min-h-screen bg-zinc-950 text-zinc-100">
            <nav className="border-b border-zinc-800 bg-zinc-950">
                <div className="mx-auto flex h-16 max-w-7xl items-center px-4 sm:px-6 lg:px-8">
                    <Link href="/" className="flex items-center gap-3">
                        <QuizPlatformLogo
                            markClassName="h-10 w-10"
                            labelClassName="hidden text-sm font-semibold text-zinc-100 sm:inline"
                        />
                    </Link>
                </div>
            </nav>

            {header && (
                <header className="border-b border-zinc-800 bg-zinc-950">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            {(flash.success || flash.error) && (
                <div className="mx-auto mt-6 max-w-7xl px-4 sm:px-6 lg:px-8">
                    {flash.success && (
                        <div className="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-200">
                            {flash.success}
                        </div>
                    )}
                    {flash.error && (
                        <div className="rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm font-medium text-red-200">
                            {flash.error}
                        </div>
                    )}
                </div>
            )}

            <main className="bg-zinc-950">{children}</main>
        </div>
    );
}
