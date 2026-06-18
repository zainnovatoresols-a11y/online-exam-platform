import axios from 'axios';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

type ProctoringAttempt = {
    id: number;
    access_token?: string | null;
    is_public?: boolean;
};

type ProctoringEventType =
    | 'tab_hidden'
    | 'window_blur'
    | 'fullscreen_exited'
    | 'copy_attempt'
    | 'paste_attempt'
    | 'cut_attempt'
    | 'right_click_attempt'
    | 'shortcut_attempt'
    | 'drag_attempt'
    | 'drop_attempt'
    | 'proctoring_violation_acknowledged';

type MetadataValue = string | number | boolean | null;
type Metadata = Record<string, MetadataValue>;

export type ProctoringViolation = {
    reason: 'tab_hidden' | 'window_blur' | 'fullscreen_exited';
    title: string;
    message: string;
};

const FRONTEND_DEDUPE_MS = 3000;

export function useProctoringGuards(
    attempt: ProctoringAttempt,
    disabled = false,
) {
    const eventUrl = useMemo(() => proctoringRoute(attempt), [attempt]);
    const blockedMessageTimerRef = useRef<number | null>(null);
    const sentAtRef = useRef<Map<string, number>>(new Map());
    const [latestBlockedAction, setLatestBlockedAction] = useState<string | null>(
        null,
    );
    const [violation, setViolation] = useState<ProctoringViolation | null>(
        null,
    );

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

    const showBlockedMessage = useCallback((message: string) => {
        setLatestBlockedAction(message);

        if (blockedMessageTimerRef.current !== null) {
            window.clearTimeout(blockedMessageTimerRef.current);
        }

        blockedMessageTimerRef.current = window.setTimeout(() => {
            setLatestBlockedAction(null);
            blockedMessageTimerRef.current = null;
        }, 5000);
    }, []);

    const acknowledgeViolation = useCallback(() => {
        if (violation) {
            sendEvent('proctoring_violation_acknowledged', {
                reason: violation.reason,
            });
        }

        setViolation(null);
    }, [sendEvent, violation]);

    const blockEvent = useCallback(
        (
            event: Event,
            eventType: ProctoringEventType,
            message: string,
            metadata: Metadata = {},
        ) => {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            sendEvent(eventType, metadata);
            showBlockedMessage(message);
        },
        [sendEvent, showBlockedMessage],
    );

    useEffect(() => {
        return () => {
            if (blockedMessageTimerRef.current !== null) {
                window.clearTimeout(blockedMessageTimerRef.current);
            }
        };
    }, []);

    useEffect(() => {
        if (
            disabled ||
            typeof window === 'undefined' ||
            typeof document === 'undefined'
        ) {
            return;
        }

        const markViolation = (reason: ProctoringViolation['reason']) => {
            setViolation(violationFor(reason));
        };

        const handleVisibilityChange = () => {
            if (document.visibilityState === 'hidden') {
                markViolation('tab_hidden');
            }
        };

        const handleWindowBlur = () => {
            markViolation('window_blur');
        };

        const handleFullscreenChange = () => {
            if (! document.fullscreenElement) {
                markViolation('fullscreen_exited');
            }
        };

        const handleCopy = (event: ClipboardEvent) => {
            blockEvent(
                event,
                'copy_attempt',
                'Copy is disabled during this assessment.',
            );
        };

        const handlePaste = (event: ClipboardEvent) => {
            blockEvent(
                event,
                'paste_attempt',
                'Paste is disabled during this assessment.',
            );
        };

        const handleCut = (event: ClipboardEvent) => {
            blockEvent(
                event,
                'cut_attempt',
                'Cut is disabled during this assessment.',
            );
        };

        const handleContextMenu = (event: MouseEvent) => {
            blockEvent(
                event,
                'right_click_attempt',
                'Right click is disabled during this assessment.',
                {
                    button: event.button,
                },
            );
        };

        const handleDragStart = (event: DragEvent) => {
            blockEvent(
                event,
                'drag_attempt',
                'Dragging content is disabled during this assessment.',
            );
        };

        const handleDragOver = (event: DragEvent) => {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
        };

        const handleDrop = (event: DragEvent) => {
            blockEvent(
                event,
                'drop_attempt',
                'Dropping content is disabled during this assessment.',
            );
        };

        const handleKeyDown = (event: KeyboardEvent) => {
            if (! shouldBlockShortcut(event)) {
                return;
            }

            blockEvent(
                event,
                'shortcut_attempt',
                'This keyboard shortcut is disabled during this assessment.',
                {
                    key: event.key,
                    code: event.code,
                    ctrl_key: event.ctrlKey,
                    meta_key: event.metaKey,
                    alt_key: event.altKey,
                    shift_key: event.shiftKey,
                },
            );
        };

        const options = { capture: true };

        document.addEventListener('visibilitychange', handleVisibilityChange);
        window.addEventListener('blur', handleWindowBlur);
        document.addEventListener('fullscreenchange', handleFullscreenChange);
        document.addEventListener('copy', handleCopy, options);
        document.addEventListener('paste', handlePaste, options);
        document.addEventListener('cut', handleCut, options);
        document.addEventListener('contextmenu', handleContextMenu, options);
        document.addEventListener('dragstart', handleDragStart, options);
        document.addEventListener('dragover', handleDragOver, options);
        document.addEventListener('drop', handleDrop, options);
        document.addEventListener('keydown', handleKeyDown, options);

        return () => {
            document.removeEventListener(
                'visibilitychange',
                handleVisibilityChange,
            );
            window.removeEventListener('blur', handleWindowBlur);
            document.removeEventListener(
                'fullscreenchange',
                handleFullscreenChange,
            );
            document.removeEventListener('copy', handleCopy, options);
            document.removeEventListener('paste', handlePaste, options);
            document.removeEventListener('cut', handleCut, options);
            document.removeEventListener('contextmenu', handleContextMenu, options);
            document.removeEventListener('dragstart', handleDragStart, options);
            document.removeEventListener('dragover', handleDragOver, options);
            document.removeEventListener('drop', handleDrop, options);
            document.removeEventListener('keydown', handleKeyDown, options);
        };
    }, [blockEvent, disabled]);

    return {
        acknowledgeViolation,
        latestBlockedAction,
        violation,
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

function shouldBlockShortcut(event: KeyboardEvent): boolean {
    if (event.key === 'PrintScreen') {
        return true;
    }

    const key = event.key.toLowerCase();
    const hasPrimaryModifier = event.ctrlKey || event.metaKey;

    if (['f5', 'f12'].includes(key)) {
        return true;
    }

    if (
        hasPrimaryModifier &&
        event.shiftKey &&
        ['c', 'i', 'j'].includes(key)
    ) {
        return true;
    }

    return (
        hasPrimaryModifier &&
        ['a', 'c', 'f', 'p', 'r', 's', 'u', 'v', 'x'].includes(key)
    );
}

function violationFor(reason: ProctoringViolation['reason']): ProctoringViolation {
    if (reason === 'fullscreen_exited') {
        return {
            reason,
            title: 'Fullscreen was exited',
            message:
                'The assessment was interrupted because fullscreen mode changed.',
        };
    }

    if (reason === 'tab_hidden') {
        return {
            reason,
            title: 'Tab switch detected',
            message:
                'The assessment was interrupted because the test tab was hidden.',
        };
    }

    return {
        reason,
        title: 'Test focus changed',
        message:
            'The assessment was interrupted because the browser window lost focus.',
    };
}
