import axios from 'axios';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

type ProctoringAttempt = {
    id: number;
    access_token?: string | null;
    is_public?: boolean;
};

type ProctoringEventType =
    | 'tab_hidden'
    | 'tab_visible'
    | 'window_blur'
    | 'window_focus'
    | 'fullscreen_entered'
    | 'fullscreen_exited'
    | 'copy_attempt'
    | 'paste_attempt'
    | 'cut_attempt'
    | 'right_click_attempt'
    | 'shortcut_attempt';

type MetadataValue = string | number | boolean | null;
type Metadata = Record<string, MetadataValue>;

const FRONTEND_DEDUPE_MS = 3000;

export function useProctoringEvents(
    attempt: ProctoringAttempt,
    disabled = false,
) {
    const eventUrl = useMemo(() => proctoringRoute(attempt), [attempt]);
    const autoFullscreenAttemptedRef = useRef(false);
    const interactionFullscreenAttemptedRef = useRef(false);
    const sentAtRef = useRef<Map<string, number>>(new Map());
    const [fullscreenActive, setFullscreenActive] = useState(false);
    const [fullscreenSupported, setFullscreenSupported] = useState(false);

    const sendEvent = useCallback(
        (eventType: ProctoringEventType, metadata: Metadata = {}) => {
            if (disabled) {
                return;
            }

            const payloadMetadata = {
                ...baseMetadata(),
                ...metadata,
            };
            const eventKey = `${eventType}:${metadataSignature(payloadMetadata)}`;
            const now = Date.now();
            const lastSentAt = sentAtRef.current.get(eventKey);

            if (lastSentAt && now - lastSentAt < FRONTEND_DEDUPE_MS) {
                return;
            }

            sentAtRef.current.set(eventKey, now);

            void axios
                .post(
                    eventUrl,
                    {
                        event_type: eventType,
                        occurred_at: new Date().toISOString(),
                        metadata: payloadMetadata,
                    },
                    {
                        headers: {
                            Accept: 'application/json',
                        },
                    },
                )
                .catch(() => undefined);
        },
        [disabled, eventUrl],
    );

    useEffect(() => {
        if (typeof document === 'undefined') {
            return;
        }

        setFullscreenActive(Boolean(document.fullscreenElement));
        setFullscreenSupported(Boolean(document.fullscreenEnabled));
    }, []);

    useEffect(() => {
        if (
            disabled ||
            typeof window === 'undefined' ||
            typeof document === 'undefined'
        ) {
            return;
        }

        const handleVisibilityChange = () => {
            sendEvent(
                document.visibilityState === 'hidden'
                    ? 'tab_hidden'
                    : 'tab_visible',
                {
                    visibility_state: document.visibilityState,
                },
            );
        };

        const handleWindowBlur = () => {
            sendEvent('window_blur');
        };

        const handleWindowFocus = () => {
            sendEvent('window_focus');
        };

        const handleFullscreenChange = () => {
            const active = Boolean(document.fullscreenElement);

            setFullscreenActive(active);
            sendEvent(active ? 'fullscreen_entered' : 'fullscreen_exited', {
                fullscreen: active,
            });
        };

        const handleCopy = () => {
            sendEvent('copy_attempt');
        };

        const handlePaste = () => {
            sendEvent('paste_attempt');
        };

        const handleCut = () => {
            sendEvent('cut_attempt');
        };

        const handleContextMenu = (event: MouseEvent) => {
            sendEvent('right_click_attempt', {
                button: event.button,
            });
        };

        const handleKeyDown = (event: KeyboardEvent) => {
            if (! shouldTrackShortcut(event)) {
                return;
            }

            sendEvent('shortcut_attempt', {
                key: event.key,
                ctrl_key: event.ctrlKey,
                meta_key: event.metaKey,
                alt_key: event.altKey,
                shift_key: event.shiftKey,
            });
        };

        document.addEventListener('visibilitychange', handleVisibilityChange);
        window.addEventListener('blur', handleWindowBlur);
        window.addEventListener('focus', handleWindowFocus);
        document.addEventListener('fullscreenchange', handleFullscreenChange);
        document.addEventListener('copy', handleCopy);
        document.addEventListener('paste', handlePaste);
        document.addEventListener('cut', handleCut);
        document.addEventListener('contextmenu', handleContextMenu);
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener(
                'visibilitychange',
                handleVisibilityChange,
            );
            window.removeEventListener('blur', handleWindowBlur);
            window.removeEventListener('focus', handleWindowFocus);
            document.removeEventListener(
                'fullscreenchange',
                handleFullscreenChange,
            );
            document.removeEventListener('copy', handleCopy);
            document.removeEventListener('paste', handlePaste);
            document.removeEventListener('cut', handleCut);
            document.removeEventListener('contextmenu', handleContextMenu);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [disabled, sendEvent]);

    const enterFullscreen = useCallback(async (): Promise<boolean> => {
        if (
            typeof document === 'undefined' ||
            ! document.fullscreenEnabled ||
            document.fullscreenElement
        ) {
            return false;
        }

        try {
            await document.documentElement.requestFullscreen();

            return true;
        } catch {
            // Browser fullscreen failures are non-blocking for this phase.
            return false;
        }
    }, []);

    useEffect(() => {
        if (
            disabled ||
            ! fullscreenSupported ||
            fullscreenActive ||
            autoFullscreenAttemptedRef.current
        ) {
            return;
        }

        autoFullscreenAttemptedRef.current = true;

        const timer = window.setTimeout(() => {
            void enterFullscreen();
        }, 300);

        return () => window.clearTimeout(timer);
    }, [disabled, enterFullscreen, fullscreenActive, fullscreenSupported]);

    useEffect(() => {
        if (
            disabled ||
            ! fullscreenSupported ||
            fullscreenActive ||
            interactionFullscreenAttemptedRef.current
        ) {
            return;
        }

        const handleCandidateInteraction = () => {
            interactionFullscreenAttemptedRef.current = true;
            void enterFullscreen();
        };

        window.addEventListener('pointerdown', handleCandidateInteraction, true);
        window.addEventListener('keydown', handleCandidateInteraction, true);

        return () => {
            window.removeEventListener(
                'pointerdown',
                handleCandidateInteraction,
                true,
            );
            window.removeEventListener('keydown', handleCandidateInteraction, true);
        };
    }, [disabled, enterFullscreen, fullscreenActive, fullscreenSupported]);

    return {
        enterFullscreen,
        fullscreenActive,
        fullscreenSupported,
    };
}

function proctoringRoute(attempt: ProctoringAttempt): string {
    if (attempt.is_public && attempt.access_token) {
        return route(
            'candidate.public-attempts.proctoring-events.store',
            attempt.access_token,
        );
    }

    return route('candidate.attempts.proctoring-events.store', attempt.id);
}

function baseMetadata(): Metadata {
    return {
        visibility_state: document.visibilityState,
        fullscreen: Boolean(document.fullscreenElement),
        screen_width: window.screen.width,
        screen_height: window.screen.height,
        language: navigator.language,
    };
}

function metadataSignature(metadata: Metadata): string {
    return JSON.stringify(
        Object.keys(metadata)
            .sort()
            .reduce<Metadata>((sorted, key) => {
                sorted[key] = metadata[key];

                return sorted;
            }, {}),
    );
}

function shouldTrackShortcut(event: KeyboardEvent): boolean {
    if (event.key === 'PrintScreen') {
        return true;
    }

    const key = event.key.toLowerCase();
    const hasModifier = event.ctrlKey || event.metaKey || event.altKey;
    const trackedKeys = ['a', 'c', 'p', 's', 'u', 'v', 'x'];

    return hasModifier && trackedKeys.includes(key);
}
