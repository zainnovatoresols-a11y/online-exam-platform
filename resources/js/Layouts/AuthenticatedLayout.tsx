import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useState } from 'react';

export default function Authenticated({
    header,
    children,
    theme = 'light',
}: PropsWithChildren<{ header?: ReactNode; theme?: 'light' | 'dark' }>) {
    const { auth, flash } = usePage<PageProps>().props;
    const user = auth.user;
    const isDark = theme === 'dark';

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    if (!user) {
        return null;
    }

    return (
        <div className={isDark ? 'min-h-screen bg-zinc-950 text-zinc-100' : 'min-h-screen bg-gray-100'}>
            <nav
                className={
                    isDark
                        ? 'border-b border-zinc-800 bg-zinc-950'
                        : 'border-b border-gray-100 bg-white'
                }
            >
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    <ApplicationLogo
                                        className={
                                            isDark
                                                ? 'block h-9 w-auto fill-current text-zinc-100'
                                                : 'block h-9 w-auto fill-current text-gray-800'
                                        }
                                    />
                                </Link>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <Link
                                    href={route('dashboard')}
                                    className={
                                        'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none ' +
                                        (route().current('dashboard')
                                            ? isDark
                                                ? 'border-emerald-400 text-white focus:border-emerald-300'
                                                : 'border-indigo-400 text-gray-900 focus:border-indigo-700'
                                            : isDark
                                              ? 'border-transparent text-zinc-400 hover:border-zinc-700 hover:text-zinc-100 focus:border-zinc-700 focus:text-zinc-100'
                                              : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 focus:border-gray-300 focus:text-gray-700')
                                    }
                                >
                                    Dashboard
                                </Link>
                            </div>
                        </div>

                        <div className="hidden sm:ms-6 sm:flex sm:items-center">
                            <div className="relative ms-3">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className={
                                                    isDark
                                                        ? 'inline-flex items-center rounded-md border border-transparent bg-zinc-950 px-3 py-2 text-sm font-medium leading-4 text-zinc-300 transition duration-150 ease-in-out hover:text-white focus:outline-none'
                                                        : 'inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none'
                                                }
                                            >
                                                {user.name}

                                                <svg
                                                    className="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content
                                        contentClasses={
                                            isDark
                                                ? 'border border-zinc-800 bg-zinc-950 py-1'
                                                : 'py-1 bg-white'
                                        }
                                    >
                                        <Dropdown.Link
                                            href={route('profile.edit')}
                                            className={
                                                isDark
                                                    ? 'text-zinc-200 hover:bg-zinc-900 focus:bg-zinc-900'
                                                    : ''
                                            }
                                        >
                                            Profile
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                            className={
                                                isDark
                                                    ? 'text-zinc-200 hover:bg-zinc-900 focus:bg-zinc-900'
                                                    : ''
                                            }
                                        >
                                            Log Out
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                className={
                                    isDark
                                        ? 'inline-flex items-center justify-center rounded-md p-2 text-zinc-400 transition duration-150 ease-in-out hover:bg-zinc-900 hover:text-zinc-100 focus:bg-zinc-900 focus:text-zinc-100 focus:outline-none'
                                        : 'inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none'
                                }
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        className={
                                            !showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={
                                            showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' sm:hidden'
                    }
                >
                    <div className="space-y-1 pb-3 pt-2">
                        <Link
                            href={route('dashboard')}
                            className={
                                'flex w-full items-start border-l-4 py-2 pe-4 ps-3 text-base font-medium transition duration-150 ease-in-out focus:outline-none ' +
                                (route().current('dashboard')
                                    ? isDark
                                        ? 'border-emerald-400 bg-emerald-400/10 text-emerald-200 focus:border-emerald-300 focus:bg-emerald-400/15'
                                        : 'border-indigo-400 bg-indigo-50 text-indigo-700 focus:border-indigo-700 focus:bg-indigo-100 focus:text-indigo-800'
                                    : isDark
                                      ? 'border-transparent text-zinc-400 hover:border-zinc-700 hover:bg-zinc-900 hover:text-zinc-100 focus:border-zinc-700 focus:bg-zinc-900 focus:text-zinc-100'
                                      : 'border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 focus:border-gray-300 focus:bg-gray-50 focus:text-gray-800')
                            }
                        >
                            Dashboard
                        </Link>
                    </div>

                    <div
                        className={
                            isDark
                                ? 'border-t border-zinc-800 pb-1 pt-4'
                                : 'border-t border-gray-200 pb-1 pt-4'
                        }
                    >
                        <div className="px-4">
                            <div
                                className={
                                    isDark
                                        ? 'text-base font-medium text-zinc-100'
                                        : 'text-base font-medium text-gray-800'
                                }
                            >
                                {user.name}
                            </div>
                            <div
                                className={
                                    isDark
                                        ? 'text-sm font-medium text-zinc-500'
                                        : 'text-sm font-medium text-gray-500'
                                }
                            >
                                {user.email}
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <Link
                                href={route('profile.edit')}
                                className={
                                    isDark
                                        ? 'flex w-full items-start border-l-4 border-transparent py-2 pe-4 ps-3 text-base font-medium text-zinc-400 transition duration-150 ease-in-out hover:border-zinc-700 hover:bg-zinc-900 hover:text-zinc-100 focus:border-zinc-700 focus:bg-zinc-900 focus:text-zinc-100 focus:outline-none'
                                        : 'flex w-full items-start border-l-4 border-transparent py-2 pe-4 ps-3 text-base font-medium text-gray-600 transition duration-150 ease-in-out hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 focus:border-gray-300 focus:bg-gray-50 focus:text-gray-800 focus:outline-none'
                                }
                            >
                                Profile
                            </Link>
                            <Link
                                method="post"
                                href={route('logout')}
                                as="button"
                                className={
                                    isDark
                                        ? 'flex w-full items-start border-l-4 border-transparent py-2 pe-4 ps-3 text-base font-medium text-zinc-400 transition duration-150 ease-in-out hover:border-zinc-700 hover:bg-zinc-900 hover:text-zinc-100 focus:border-zinc-700 focus:bg-zinc-900 focus:text-zinc-100 focus:outline-none'
                                        : 'flex w-full items-start border-l-4 border-transparent py-2 pe-4 ps-3 text-base font-medium text-gray-600 transition duration-150 ease-in-out hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 focus:border-gray-300 focus:bg-gray-50 focus:text-gray-800 focus:outline-none'
                                }
                            >
                                Log Out
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header
                    className={
                        isDark
                            ? 'border-b border-zinc-800 bg-zinc-950'
                            : 'bg-white shadow'
                    }
                >
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            {(flash.success || flash.warning || flash.error) && (
                <div className="mx-auto mt-6 max-w-7xl space-y-3 px-4 sm:px-6 lg:px-8">
                    {flash.success && (
                        <div
                            className={
                                isDark
                                    ? 'rounded-md border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-200'
                                    : 'rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800'
                            }
                        >
                            {flash.success}
                        </div>
                    )}
                    {flash.warning && (
                        <div
                            className={
                                isDark
                                    ? 'rounded-md border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-sm font-medium text-amber-200'
                                    : 'rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800'
                            }
                        >
                            {flash.warning}
                        </div>
                    )}
                    {flash.error && (
                        <div
                            className={
                                isDark
                                    ? 'rounded-md border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm font-medium text-red-200'
                                    : 'rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800'
                            }
                        >
                            {flash.error}
                        </div>
                    )}
                </div>
            )}

            <main className={isDark ? 'bg-zinc-950' : undefined}>{children}</main>
        </div>
    );
}
