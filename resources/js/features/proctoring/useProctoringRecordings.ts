import axios from 'axios';
import { RefObject, useCallback, useEffect, useMemo, useRef, useState } from 'react';

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
    cameraVideoRef: RefObject<HTMLVideoElement>;
    screenVideoRef: RefObject<HTMLVideoElement>;
    startCamera: () => Promise<void>;
    startScreen: () => Promise<void>;
    stopAllRecordings: (reason?: string) => Promise<void>;
    recordContinueWithViolation: () => void;
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
    const cameraVideoRef = useRef<HTMLVideoElement>(null);
    const screenVideoRef = useRef<HTMLVideoElement>(null);
    const cameraStreamRef = useRef<MediaStream | null>(null);
    const screenStreamRef = useRef<MediaStream | null>(null);
    const cameraRecorderRef = useRef<MediaRecorder | null>(null);
    const screenRecorderRef = useRef<MediaRecorder | null>(null);
    const cameraSequenceRef = useRef(0);
    const screenSequenceRef = useRef(0);
    const cameraStartedRef = useRef(false);
    const stoppingRef = useRef<Set<RecordingType>>(new Set());
    const cameraSessionStartedRef = useRef(false);
    const screenSessionStartedRef = useRef(false);
    const [cameraStatus, setCameraStatus] = useState<RecordingStatus>('idle');
    const [screenStatus, setScreenStatus] = useState<RecordingStatus>('idle');
    const [cameraMessage, setCameraMessage] = useState<string | null>(null);
    const [screenMessage, setScreenMessage] = useState<string | null>(null);

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

            try {
                const recorder = recorderRef.current;

                if (recorder && recorder.state !== 'inactive') {
                    try {
                        recorder.requestData();
                    } catch {
                        // Some browsers throw if requestData is called during stop.
                    }

                    recorder.stop();
                }

                streamRef.current?.getTracks().forEach((track) => track.stop());
                streamRef.current = null;
                recorderRef.current = null;
                const shouldNotifyServer = sessionStartedRef.current;
                sessionStartedRef.current = false;

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

                if (shouldNotifyServer) {
                    setStatus('stopped');
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

    const startRecorder = useCallback(
        (
            recordingType: RecordingType,
            stream: MediaStream,
            mimeType: string,
        ) => {
            const recorder = new MediaRecorder(stream, {
                mimeType: mimeType || undefined,
                videoBitsPerSecond:
                    recordingType === 'camera'
                        ? CAMERA_BITS_PER_SECOND
                        : SCREEN_BITS_PER_SECOND,
            });

            recorder.ondataavailable = (event) => {
                if (event.data.size === 0) {
                    return;
                }

                const sequenceRef =
                    recordingType === 'camera'
                        ? cameraSequenceRef
                        : screenSequenceRef;
                const sequence = sequenceRef.current + 1;
                sequenceRef.current = sequence;

                void uploadChunk(recordingType, event.data, sequence, mimeType);
            };

            recorder.onerror = () => {
                sendEvent(`${recordingType}_recording_error`, {
                    recording_type: recordingType,
                    stage: 'media_recorder',
                });

                if (recordingType === 'camera') {
                    setCameraStatus('error');
                    setCameraMessage('Camera recording failed.');
                } else {
                    setScreenStatus('error');
                    setScreenMessage('Screen recording failed.');
                }
            };

            recorder.start(CHUNK_DURATION_MS);

            if (recordingType === 'camera') {
                cameraRecorderRef.current = recorder;
            } else {
                screenRecorderRef.current = recorder;
            }
        },
        [sendEvent, uploadChunk],
    );

    const startCamera = useCallback(async () => {
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
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 480 },
                    height: { ideal: 360 },
                    frameRate: { ideal: 5, max: 10 },
                },
                audio: false,
            });
            const mimeType = preferredMimeType();

            cameraStreamRef.current = stream;
            attachStream(cameraVideoRef.current, stream);
            sendEvent('camera_recording_permission_granted');
            await startRecordingSession('camera', mimeType, {
                video_tracks: stream.getVideoTracks().length,
            });
            cameraSessionStartedRef.current = true;
            startRecorder('camera', stream, mimeType);
            setCameraStatus('recording');
            setCameraMessage('Camera recording active.');
        } catch (error) {
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
    }, [sendEvent, startRecorder, startRecordingSession]);

    const startScreen = useCallback(async () => {
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
            const stream = await navigator.mediaDevices.getDisplayMedia({
                video: {
                    frameRate: { ideal: 5, max: 10 },
                },
                audio: false,
            });
            const mimeType = preferredMimeType();
            const screenTrack = stream.getVideoTracks()[0];

            screenStreamRef.current = stream;
            attachStream(screenVideoRef.current, stream);
            sendEvent('screen_recording_permission_granted');
            await startRecordingSession('screen', mimeType, {
                video_tracks: stream.getVideoTracks().length,
            });
            screenSessionStartedRef.current = true;
            startRecorder('screen', stream, mimeType);
            setScreenStatus('recording');
            setScreenMessage('Screen recording active.');

            screenTrack?.addEventListener('ended', () => {
                setScreenStatus('stopped');
                setScreenMessage('Screen sharing stopped. Please restart it.');
                void stopRecording('screen', 'screen_share_ended');
            });
        } catch (error) {
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
    }, [sendEvent, startRecorder, startRecordingSession, stopRecording]);

    const stopAllRecordings = useCallback(
        async (reason = 'stopped') => {
            await Promise.all([
                stopRecording('camera', reason),
                stopRecording('screen', reason),
            ]);
        },
        [stopRecording],
    );

    const recordContinueWithViolation = useCallback(() => {
        if (cameraStatus !== 'recording') {
            sendEvent('camera_recording_error', {
                reason: 'candidate_continued_without_camera',
                status: cameraStatus,
            });
        }

        if (screenStatus !== 'recording') {
            sendEvent('screen_recording_error', {
                reason: 'candidate_continued_without_screen',
                status: screenStatus,
            });
        }
    }, [cameraStatus, screenStatus, sendEvent]);

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
        cameraVideoRef,
        screenVideoRef,
        startCamera,
        startScreen,
        stopAllRecordings,
        recordContinueWithViolation,
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

function attachStream(
    videoElement: HTMLVideoElement | null,
    stream: MediaStream,
): void {
    if (! videoElement) {
        return;
    }

    videoElement.srcObject = stream;
    videoElement.muted = true;
    videoElement.playsInline = true;
    void videoElement.play().catch(() => undefined);
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
