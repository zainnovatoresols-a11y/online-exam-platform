import axios from 'axios';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

type ProctoringAttempt = {
    id: number;
    access_token?: string | null;
    is_public?: boolean;
};

export type RecordingStatus =
    | 'idle'
    | 'requesting'
    | 'recording'
    | 'denied'
    | 'unavailable'
    | 'stopped'
    | 'error';

type RecordingType = 'camera' | 'screen';

type RecordingEventType =
    | 'camera_recording_permission_granted'
    | 'camera_recording_permission_denied'
    | 'camera_recording_chunk_failed'
    | 'camera_recording_error'
    | 'screen_recording_permission_granted'
    | 'screen_recording_permission_denied'
    | 'screen_recording_chunk_failed'
    | 'screen_recording_error';

type MetadataValue = string | number | boolean | null;
type Metadata = Record<string, MetadataValue>;

export type ProctoringRecordingControls = {
    cameraStatus: RecordingStatus;
    screenStatus: RecordingStatus;
    cameraMessage: string | null;
    screenMessage: string | null;
    cameraStream: MediaStream | null;
    startCamera: () => Promise<void>;
    startScreen: () => Promise<void>;
    stopAllRecordings: (reason?: string) => Promise<void>;
};

const CHUNK_DURATION_MS = 10_000;
const CAMERA_BITS_PER_SECOND = 350_000;
const SCREEN_BITS_PER_SECOND = 700_000;

export function useProctoringRecordings(
    attempt: ProctoringAttempt,
    disabled = false,
): ProctoringRecordingControls {
    const routes = useMemo(() => recordingRoutes(attempt), [attempt]);
    const disabledRef = useRef(disabled);
    const cameraStreamRef = useRef<MediaStream | null>(null);
    const screenStreamRef = useRef<MediaStream | null>(null);
    const cameraRecorderRef = useRef<MediaRecorder | null>(null);
    const screenRecorderRef = useRef<MediaRecorder | null>(null);
    const cameraSequenceRef = useRef(0);
    const screenSequenceRef = useRef(0);
    const cameraChunkTimerRef = useRef<number | null>(null);
    const screenChunkTimerRef = useRef<number | null>(null);
    const cameraStartedRef = useRef(false);
    const stoppingRef = useRef<Set<RecordingType>>(new Set());
    const cameraSessionStartedRef = useRef(false);
    const screenSessionStartedRef = useRef(false);
    const screenSessionIdRef = useRef(0);
    const [cameraStatus, setCameraStatus] = useState<RecordingStatus>('idle');
    const [screenStatus, setScreenStatus] = useState<RecordingStatus>('idle');
    const [cameraMessage, setCameraMessage] = useState<string | null>(null);
    const [screenMessage, setScreenMessage] = useState<string | null>(null);
    const [cameraStream, setCameraStream] = useState<MediaStream | null>(null);

    useEffect(() => {
        disabledRef.current = disabled;
    }, [disabled]);

    const sendEvent = useCallback(
        (eventType: RecordingEventType, metadata: Metadata = {}) => {
            if (disabledRef.current) {
                return;
            }

            void axios
                .post(
                    routes.event,
                    {
                        event_type: eventType,
                        occurred_at: new Date().toISOString(),
                        metadata: {
                            ...baseMetadata(),
                            ...metadata,
                        },
                    },
                    {
                        headers: {
                            Accept: 'application/json',
                        },
                    },
                )
                .catch(() => undefined);
        },
        [routes.event],
    );

    const startRecordingSession = useCallback(
        async (
            recordingType: RecordingType,
            mimeType: string,
            metadata: Metadata = {},
        ) => {
            await axios.post(
                routes.start,
                {
                    recording_type: recordingType,
                    mime_type: mimeType,
                    metadata: {
                        ...baseMetadata(),
                        ...metadata,
                    },
                },
                {
                    headers: {
                        Accept: 'application/json',
                    },
                },
            );
        },
        [routes.start],
    );

    const uploadChunk = useCallback(
        async (
            recordingType: RecordingType,
            blob: Blob,
            sequence: number,
            mimeType: string,
        ) => {
            if (disabledRef.current || blob.size === 0) {
                return;
            }

            const formData = new FormData();
            const metadata = baseMetadata();

            formData.append('recording_type', recordingType);
            formData.append(
                'chunk',
                blob,
                `${recordingType}_${String(sequence).padStart(6, '0')}.webm`,
            );
            formData.append('sequence', String(sequence));
            formData.append('duration_ms', String(CHUNK_DURATION_MS));
            formData.append('recorded_at', new Date().toISOString());
            formData.append('mime_type', mimeType || 'video/webm');

            Object.entries(metadata).forEach(([key, value]) => {
                if (value !== null) {
                    formData.append(`metadata[${key}]`, String(value));
                }
            });

            try {
                await axios.post(routes.chunk, formData, {
                    headers: {
                        Accept: 'application/json',
                    },
                });
            } catch {
                sendEvent(`${recordingType}_recording_chunk_failed`, {
                    recording_type: recordingType,
                    sequence,
                });

                if (recordingType === 'camera') {
                    setCameraMessage(
                        'Camera recording upload failed. The attempt is still running.',
                    );
                } else {
                    setScreenMessage(
                        'Screen recording upload failed. The attempt is still running.',
                    );
                }
            }
        },
        [routes.chunk, sendEvent],
    );

    const stopRecording = useCallback(
        async (recordingType: RecordingType, reason = 'stopped') => {
            if (stoppingRef.current.has(recordingType)) {
                return;
            }

            stoppingRef.current.add(recordingType);

            const recorderRef =
                recordingType === 'camera'
                    ? cameraRecorderRef
                    : screenRecorderRef;
            const streamRef =
                recordingType === 'camera' ? cameraStreamRef : screenStreamRef;
            const setStatus =
                recordingType === 'camera' ? setCameraStatus : setScreenStatus;
            const sessionStartedRef =
                recordingType === 'camera'
                    ? cameraSessionStartedRef
                    : screenSessionStartedRef;
            const timerRef =
                recordingType === 'camera'
                    ? cameraChunkTimerRef
                    : screenChunkTimerRef;

            try {
                const recorder = recorderRef.current;
                const stream = streamRef.current;

                if (recorder && recorder.state !== 'inactive') {
                    try {
                        recorder.stop();
                    } catch {
                        // Some browsers throw if stop is called during a state change.
                    }
                }

                if (timerRef.current !== null) {
                    window.clearTimeout(timerRef.current);
                    timerRef.current = null;
                }

                stream?.getTracks().forEach((track) => track.stop());

                if (streamRef.current === stream) {
                    streamRef.current = null;

                    if (recordingType === 'camera') {
                        setCameraStream(null);
                    }
                }

                if (recorderRef.current === recorder) {
                    recorderRef.current = null;
                }

                const shouldNotifyServer = sessionStartedRef.current;
                sessionStartedRef.current = false;

                if (shouldNotifyServer) {
                    setStatus('stopped');
                }

                if (shouldNotifyServer && ! disabledRef.current) {
                    await axios.post(
                        routes.stop,
                        {
                            recording_type: recordingType,
                            reason,
                            metadata: {
                                ...baseMetadata(),
                                reason,
                            },
                        },
                        {
                            headers: {
                                Accept: 'application/json',
                            },
                        },
                    );
                }
            } catch {
                sendEvent(`${recordingType}_recording_error`, {
                    recording_type: recordingType,
                    reason,
                    stage: 'stop',
                });
            } finally {
                stoppingRef.current.delete(recordingType);
            }
        },
        [routes.stop, sendEvent],
    );

    const waitForStopCleanup = useCallback(async (recordingType: RecordingType) => {
        const startedAt = Date.now();

        while (
            stoppingRef.current.has(recordingType) &&
            Date.now() - startedAt < 3000
        ) {
            await wait(100);
        }
    }, []);

    const startRecorderChunk = useCallback(
        (
            recordingType: RecordingType,
            stream: MediaStream,
            mimeType: string,
        ) => {
            if (disabledRef.current || ! stream.active) {
                return;
            }

            const sessionStartedRef =
                recordingType === 'camera'
                    ? cameraSessionStartedRef
                    : screenSessionStartedRef;

            if (! sessionStartedRef.current) {
                return;
            }

            const timerRef =
                recordingType === 'camera'
                    ? cameraChunkTimerRef
                    : screenChunkTimerRef;
            const setStatus =
                recordingType === 'camera' ? setCameraStatus : setScreenStatus;
            const setMessage =
                recordingType === 'camera' ? setCameraMessage : setScreenMessage;
            const recordedParts: Blob[] = [];
            const recorder = new MediaRecorder(stream, {
                mimeType: mimeType || undefined,
                videoBitsPerSecond:
                    recordingType === 'camera'
                        ? CAMERA_BITS_PER_SECOND
                        : SCREEN_BITS_PER_SECOND,
            });

            recorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    recordedParts.push(event.data);
                }
            };

            recorder.onstop = () => {
                if (timerRef.current !== null) {
                    window.clearTimeout(timerRef.current);
                    timerRef.current = null;
                }

                if (recordedParts.length > 0) {
                    const sequenceRef =
                        recordingType === 'camera'
                            ? cameraSequenceRef
                            : screenSequenceRef;
                    const sequence = sequenceRef.current + 1;
                    sequenceRef.current = sequence;
                    const chunkMimeType =
                        mimeType || recorder.mimeType || 'video/webm';
                    const blob = new Blob(recordedParts, {
                        type: chunkMimeType,
                    });

                    void uploadChunk(recordingType, blob, sequence, chunkMimeType);
                }

                if (
                    sessionStartedRef.current &&
                    ! disabledRef.current &&
                    ! stoppingRef.current.has(recordingType) &&
                    stream.active
                ) {
                    window.setTimeout(() => {
                        startRecorderChunk(recordingType, stream, mimeType);
                    }, 250);
                }
            };

            recorder.onerror = () => {
                sendEvent(`${recordingType}_recording_error`, {
                    recording_type: recordingType,
                    stage: 'media_recorder',
                });

                setStatus('error');
                setMessage(
                    `${recordingType === 'camera' ? 'Camera' : 'Screen'} recording failed.`,
                );
            };

            if (recordingType === 'camera') {
                cameraRecorderRef.current = recorder;
            } else {
                screenRecorderRef.current = recorder;
            }

            recorder.start();

            timerRef.current = window.setTimeout(() => {
                if (recorder.state === 'recording') {
                    try {
                        recorder.stop();
                    } catch {
                        sendEvent(`${recordingType}_recording_error`, {
                            recording_type: recordingType,
                            stage: 'chunk_stop',
                        });
                    }
                }
            }, CHUNK_DURATION_MS);
        },
        [sendEvent, uploadChunk],
    );

    const startCamera = useCallback(async () => {
        if (stoppingRef.current.has('camera')) {
            setCameraStatus('requesting');
            setCameraMessage('Restarting camera recording...');
            await waitForStopCleanup('camera');
        }

        if (disabledRef.current || cameraRecorderRef.current?.state === 'recording') {
            return;
        }

        if (! navigator.mediaDevices?.getUserMedia || typeof MediaRecorder === 'undefined') {
            setCameraStatus('unavailable');
            setCameraMessage('Camera recording is unavailable in this browser.');
            sendEvent('camera_recording_error', {
                reason: 'camera_unavailable',
            });

            return;
        }

        setCameraStatus('requesting');
        setCameraMessage('Waiting for camera permission...');

        try {
            setMediaPermissionPromptActive(true);
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 480 },
                    height: { ideal: 360 },
                    frameRate: { ideal: 5, max: 10 },
                },
                audio: false,
            });
            setMediaPermissionPromptActive(false);
            const mimeType = preferredMimeType();

            cameraStreamRef.current = stream;
            setCameraStream(stream);
            sendEvent('camera_recording_permission_granted');
            await startRecordingSession('camera', mimeType, {
                video_tracks: stream.getVideoTracks().length,
            });
            cameraSessionStartedRef.current = true;
            startRecorderChunk('camera', stream, mimeType);
            setCameraStatus('recording');
            setCameraMessage('Camera recording active.');
        } catch (error) {
            setMediaPermissionPromptActive(false);
            const denied = isPermissionDenied(error);

            setCameraStatus(denied ? 'denied' : 'error');
            setCameraMessage(
                denied
                    ? 'Camera permission is required for this assessment.'
                    : 'Camera recording could not be started.',
            );
            sendEvent(
                denied
                    ? 'camera_recording_permission_denied'
                    : 'camera_recording_error',
                errorMetadata(error),
            );
        }
    }, [sendEvent, startRecorderChunk, startRecordingSession, waitForStopCleanup]);

    const startScreen = useCallback(async () => {
        if (stoppingRef.current.has('screen')) {
            setScreenStatus('requesting');
            setScreenMessage('Restarting screen recording...');
            await waitForStopCleanup('screen');
        }

        if (disabledRef.current || screenRecorderRef.current?.state === 'recording') {
            return;
        }

        if (! navigator.mediaDevices?.getDisplayMedia || typeof MediaRecorder === 'undefined') {
            setScreenStatus('unavailable');
            setScreenMessage('Screen recording is unavailable in this browser.');
            sendEvent('screen_recording_error', {
                reason: 'screen_unavailable',
            });

            return;
        }

        setScreenStatus('requesting');
        setScreenMessage('Waiting for screen sharing permission...');

        try {
            setMediaPermissionPromptActive(true);
            const stream = await navigator.mediaDevices.getDisplayMedia(
                displayMediaOptions(),
            );
            setMediaPermissionPromptActive(false);
            const mimeType = preferredMimeType();
            const screenTrack = stream.getVideoTracks()[0];
            const sessionId = screenSessionIdRef.current + 1;

            screenSessionIdRef.current = sessionId;
            screenStreamRef.current = stream;
            sendEvent('screen_recording_permission_granted');
            screenSessionStartedRef.current = true;
            startRecorderChunk('screen', stream, mimeType);
            setScreenStatus('recording');
            setScreenMessage('Screen recording active.');
            void startRecordingSession('screen', mimeType, {
                video_tracks: stream.getVideoTracks().length,
            }).catch((error) => {
                sendEvent('screen_recording_error', {
                    ...errorMetadata(error),
                    stage: 'start_session',
                });

                if (screenSessionIdRef.current === sessionId) {
                    setScreenMessage(
                        'Screen recording active. Server sync will retry on chunk upload.',
                    );
                }
            });

            screenTrack?.addEventListener('ended', () => {
                if (
                    screenSessionIdRef.current !== sessionId ||
                    screenStreamRef.current !== stream
                ) {
                    return;
                }

                setScreenStatus('stopped');
                setScreenMessage('Screen sharing stopped. Please restart it.');
                void stopRecording('screen', 'screen_share_ended');
            });
        } catch (error) {
            setMediaPermissionPromptActive(false);
            const denied = isPermissionDenied(error);

            setScreenStatus(denied ? 'denied' : 'error');
            setScreenMessage(
                denied
                    ? 'Screen sharing is required for this assessment.'
                    : 'Screen recording could not be started.',
            );
            sendEvent(
                denied
                    ? 'screen_recording_permission_denied'
                    : 'screen_recording_error',
                errorMetadata(error),
            );
        }
    }, [
        sendEvent,
        startRecorderChunk,
        startRecordingSession,
        stopRecording,
        waitForStopCleanup,
    ]);

    const stopAllRecordings = useCallback(
        async (reason = 'stopped') => {
            await Promise.all([
                stopRecording('camera', reason),
                stopRecording('screen', reason),
            ]);
        },
        [stopRecording],
    );

    useEffect(() => {
        if (disabled || cameraStartedRef.current) {
            return;
        }

        cameraStartedRef.current = true;
        void startCamera();
    }, [disabled, startCamera]);

    useEffect(() => {
        if (! disabled) {
            return;
        }

        void stopAllRecordings('attempt_inactive');
    }, [disabled, stopAllRecordings]);

    useEffect(() => {
        return () => {
            void stopAllRecordings('component_unmounted');
        };
    }, [stopAllRecordings]);

    return {
        cameraStatus,
        screenStatus,
        cameraMessage,
        screenMessage,
        cameraStream,
        startCamera,
        startScreen,
        stopAllRecordings,
    };
}

function recordingRoutes(attempt: ProctoringAttempt): {
    start: string;
    chunk: string;
    stop: string;
    event: string;
} {
    if (attempt.is_public && attempt.access_token) {
        return {
            start: route(
                'candidate.public-attempts.proctoring-recordings.start',
                attempt.access_token,
            ),
            chunk: route(
                'candidate.public-attempts.proctoring-recordings.chunks.store',
                attempt.access_token,
            ),
            stop: route(
                'candidate.public-attempts.proctoring-recordings.stop',
                attempt.access_token,
            ),
            event: route(
                'candidate.public-attempts.proctoring-events.store',
                attempt.access_token,
            ),
        };
    }

    return {
        start: route('candidate.attempts.proctoring-recordings.start', attempt.id),
        chunk: route(
            'candidate.attempts.proctoring-recordings.chunks.store',
            attempt.id,
        ),
        stop: route('candidate.attempts.proctoring-recordings.stop', attempt.id),
        event: route('candidate.attempts.proctoring-events.store', attempt.id),
    };
}

function preferredMimeType(): string {
    if (typeof MediaRecorder === 'undefined') {
        return 'video/webm';
    }

    return (
        [
            'video/webm;codecs=vp8,opus',
            'video/webm;codecs=vp8',
            'video/webm',
        ].find((mimeType) => MediaRecorder.isTypeSupported(mimeType)) ??
        'video/webm'
    );
}

function displayMediaOptions(): DisplayMediaStreamOptions {
    return {
        video: {
            frameRate: { ideal: 5, max: 10 },
            displaySurface: 'monitor',
        },
        audio: false,
        monitorTypeSurfaces: 'include',
        surfaceSwitching: 'exclude',
    } as DisplayMediaStreamOptions;
}

function setMediaPermissionPromptActive(active: boolean): void {
    if (typeof document === 'undefined') {
        return;
    }

    if (active) {
        document.documentElement.dataset.proctoringMediaPermissionPrompt = 'true';
        delete document.documentElement.dataset.proctoringMediaPermissionPromptEndedAt;
    } else {
        delete document.documentElement.dataset.proctoringMediaPermissionPrompt;
        document.documentElement.dataset.proctoringMediaPermissionPromptEndedAt =
            String(Date.now());
    }
}

function wait(milliseconds: number): Promise<void> {
    return new Promise((resolve) => {
        window.setTimeout(resolve, milliseconds);
    });
}

function isPermissionDenied(error: unknown): boolean {
    return error instanceof DOMException
        && ['NotAllowedError', 'PermissionDeniedError'].includes(error.name);
}

function errorMetadata(error: unknown): Metadata {
    if (error instanceof DOMException) {
        return {
            error_name: error.name,
            error_message: error.message,
        };
    }

    return {
        error_name: 'unknown_error',
    };
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
