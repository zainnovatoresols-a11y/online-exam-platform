type QuizPlatformLogoProps = {
    className?: string;
    markClassName?: string;
    labelClassName?: string;
    showLabel?: boolean;
};

export default function QuizPlatformLogo({
    className = '',
    markClassName = '',
    labelClassName = 'text-sm font-semibold text-white',
    showLabel = true,
}: QuizPlatformLogoProps) {
    return (
        <span className={`inline-flex items-center gap-2.5 ${className}`}>
            <span
                className={
                    'relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[linear-gradient(135deg,#6ee7b7_0%,#10b981_48%,#0f766e_100%)] text-emerald-950 shadow-[inset_0_1px_0_rgba(255,255,255,0.45),0_12px_28px_-18px_rgba(16,185,129,0.9)] ' +
                    markClassName
                }
                aria-hidden="true"
            >
                <span className="absolute left-1 top-1 h-3 w-3 rounded-full bg-white/35 blur-[1px]" />
                <svg
                    className="relative h-[72%] w-[72%]"
                    viewBox="0 0 48 48"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M14.2 9.5h17.1c2.2 0 4 1.8 4 4v20.8c0 2.2-1.8 4-4 4H14.2c-2.2 0-4-1.8-4-4V13.5c0-2.2 1.8-4 4-4Z"
                        fill="#F8FAFC"
                    />
                    <path
                        d="M16.8 17.2h11.6M16.8 23.7h6.8"
                        stroke="#064E3B"
                        strokeWidth="2.4"
                        strokeLinecap="round"
                    />
                    <path
                        d="M20.7 31.7c0-1.6 1.1-2.6 2.5-3.4 1.4-.8 2.4-1.5 2.4-2.9 0-1.6-1.3-2.7-3.4-2.7-1.6 0-2.8.5-3.7 1.3"
                        stroke="#047857"
                        strokeWidth="2.4"
                        strokeLinecap="round"
                    />
                    <path
                        d="M23.2 35.1h.1"
                        stroke="#047857"
                        strokeWidth="3"
                        strokeLinecap="round"
                    />
                    <circle cx="34.4" cy="31.7" r="7.2" fill="#022C22" />
                    <path
                        d="m31.1 31.9 2.1 2.1 4.5-5"
                        stroke="#A7F3D0"
                        strokeWidth="2.2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                    <path
                        d="M14.2 9.5h17.1c2.2 0 4 1.8 4 4v20.8c0 2.2-1.8 4-4 4H14.2c-2.2 0-4-1.8-4-4V13.5c0-2.2 1.8-4 4-4Z"
                        stroke="#064E3B"
                        strokeOpacity="0.16"
                        strokeWidth="1.5"
                    />
                </svg>
            </span>

            {showLabel && (
                <span className={labelClassName}>Online Quiz Platform</span>
            )}
        </span>
    );
}
