import ApplicationLogo from '@/Components/ApplicationLogo';
import QuizPlatformLogo from '@/Components/QuizPlatformLogo';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({
    children,
    theme = 'light',
}: PropsWithChildren<{ theme?: 'light' | 'dark' }>) {
    if (theme === 'dark') {
        return (
            <div className="min-h-screen bg-zinc-950 text-zinc-100">
                <nav className="border-b border-zinc-800 bg-zinc-950">
                    <div className="mx-auto flex h-16 max-w-7xl items-center px-4 sm:px-6 lg:px-8">
                        <Link href="/" className="flex items-center gap-3">
                            <QuizPlatformLogo
                                markClassName="h-10 w-10"
                                labelClassName="text-sm font-semibold text-zinc-100"
                            />
                        </Link>
                    </div>
                </nav>

                <main className="flex min-h-[calc(100vh-64px)] items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
                    <div className="w-full max-w-2xl rounded-[18px] border border-zinc-800 bg-zinc-900 p-6 shadow-2xl shadow-black/20 sm:p-8">
                        {children}
                    </div>
                </main>
            </div>
        );
    }

    return (
        <div className="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
            <div>
                <Link href="/">
                    <ApplicationLogo className="h-20 w-20 fill-current text-gray-500" />
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
                {children}
            </div>
        </div>
    );
}
