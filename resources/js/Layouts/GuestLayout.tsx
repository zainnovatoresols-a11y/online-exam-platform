import ApplicationLogo from '@/Components/ApplicationLogo';
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
                            <GuestExamLogo />
                            <span className="text-sm font-semibold text-zinc-100">
                                Online Exam Platform
                            </span>
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

function GuestExamLogo() {
    return (
        <span
            className="flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-400/20 bg-emerald-400/10 text-emerald-300"
            aria-hidden="true"
        >
            <svg
                className="h-6 w-6"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    d="M7 4.75h10A1.25 1.25 0 0 1 18.25 6v12A1.25 1.25 0 0 1 17 19.25H7A1.25 1.25 0 0 1 5.75 18V6A1.25 1.25 0 0 1 7 4.75Z"
                    stroke="currentColor"
                    strokeWidth="1.7"
                />
                <path
                    d="M9 9h6M9 12h3.5M9 15l1.25 1.25L13 13.5"
                    stroke="currentColor"
                    strokeWidth="1.7"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
                <path
                    d="M8.25 3.75h7.5"
                    stroke="currentColor"
                    strokeWidth="1.7"
                    strokeLinecap="round"
                />
            </svg>
        </span>
    );
}
