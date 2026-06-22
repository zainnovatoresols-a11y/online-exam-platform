import { Link } from '@inertiajs/react';

type AccessPathCardProps = {
    eyebrow: string;
    title: string;
    description: string;
    href: string;
    cta: string;
    helper: string;
    highlights: string[];
    variant?: 'primary' | 'secondary';
};

export default function AccessPathCard({
    eyebrow,
    title,
    description,
    href,
    cta,
    helper,
    highlights,
    variant = 'secondary',
}: AccessPathCardProps) {
    const primary = variant === 'primary';

    return (
        <div
            className={`rounded-lg border p-5 shadow-sm transition ${
                primary
                    ? 'border-zinc-900 bg-zinc-900 text-white shadow-lg'
                    : 'border-zinc-200 bg-white text-zinc-900 hover:border-zinc-300'
            }`}
        >
            <div className="space-y-4">
                <div>
                    <p
                        className={`text-sm font-semibold ${
                            primary ? 'text-zinc-400' : 'text-zinc-500'
                        }`}
                    >
                        {eyebrow}
                    </p>
                    <h2 className="mt-3 text-2xl font-semibold">
                        {title}
                    </h2>
                    <p
                        className={`mt-3 text-sm leading-6 ${
                            primary ? 'text-zinc-300' : 'text-zinc-600'
                        }`}
                    >
                        {description}
                    </p>
                </div>

                <ul className="space-y-3">
                    {highlights.map((highlight) => (
                        <li key={highlight} className="flex gap-3">
                            <span
                                className={`mt-2 h-2 w-2 shrink-0 rounded-full ${
                                    primary ? 'bg-emerald-300' : 'bg-zinc-400'
                                }`}
                            />
                            <span
                                className={`text-sm leading-6 ${
                                    primary ? 'text-zinc-200' : 'text-zinc-600'
                                }`}
                            >
                                {highlight}
                            </span>
                        </li>
                    ))}
                </ul>
            </div>

            <div className="mt-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <p
                    className={`max-w-sm text-sm leading-6 ${
                        primary ? 'text-zinc-300' : 'text-zinc-600'
                    }`}
                >
                    {helper}
                </p>

                <Link
                    href={href}
                    className={`inline-flex justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2 sm:min-w-36 ${
                        primary
                            ? 'bg-white text-zinc-900 hover:bg-zinc-100 focus:ring-white'
                            : 'bg-zinc-900 text-white hover:bg-zinc-800 focus:ring-zinc-900'
                    }`}
                >
                    {cta}
                </Link>
            </div>
        </div>
    );
}
