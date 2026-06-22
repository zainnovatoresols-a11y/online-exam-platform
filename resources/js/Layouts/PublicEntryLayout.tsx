import ApplicationLogo from '@/Components/ApplicationLogo';
import { User } from '@/types';
import { Link } from '@inertiajs/react';
import { PropsWithChildren, ReactNode } from 'react';

type PublicEntryLayoutProps = PropsWithChildren<{
    authUser?: User | null;
    eyebrow: string;
    title: string;
    description: string;
    supportingContent?: ReactNode;
    afterContent?: ReactNode;
    rightSectionClassName?: string;
}>;

export default function PublicEntryLayout({
    authUser = null,
    eyebrow,
    title,
    description,
    supportingContent,
    afterContent,
    rightSectionClassName = 'space-y-4',
    children,
}: PublicEntryLayoutProps) {
    return (
        <div className="min-h-screen bg-zinc-100 text-zinc-900 selection:bg-zinc-900 selection:text-white">
            <header className="border-b border-zinc-200 bg-white">
                <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-4">
                    <Link href="/" className="flex items-center gap-3">
                        <div className="rounded-lg bg-zinc-900 p-2 text-white shadow-sm">
                            <ApplicationLogo className="h-7 w-7 fill-current" />
                        </div>
                        <div className="min-w-0">
                            <p className="text-sm font-semibold text-zinc-950">
                                Online Exam Platform
                            </p>
                            <p className="text-sm text-zinc-600">
                                Assessment operations workspace
                            </p>
                        </div>
                    </Link>

                    <div className="flex flex-wrap items-center gap-3">
                        {authUser ? (
                            <Link
                                href={route('dashboard')}
                                className="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-zinc-400 hover:text-zinc-950 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={route('onboarding.index')}
                                    className="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                >
                                    Admin onboarding
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </header>

            <main className="mx-auto max-w-6xl px-6 py-10 sm:py-12 lg:py-16">
                <div className="grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-start">
                    <section className="space-y-8">
                        <div className="space-y-4">
                            <p className="text-sm font-semibold text-zinc-500">
                                {eyebrow}
                            </p>
                            <h1 className="max-w-3xl text-4xl font-semibold text-zinc-950 sm:text-5xl">
                                {title}
                            </h1>
                            <p className="max-w-2xl text-base leading-7 text-zinc-600 sm:text-lg">
                                {description}
                            </p>
                        </div>

                        {supportingContent}
                    </section>

                    <section className={rightSectionClassName}>{children}</section>
                </div>

                {afterContent && (
                    <div className="mt-12 border-t border-zinc-200 pt-10">
                        {afterContent}
                    </div>
                )}
            </main>
        </div>
    );
}
