import axios from 'axios';
import { FaceDetector, FilesetResolver } from '@mediapipe/tasks-vision';
import {
    type Dispatch,
    type MutableRefObject,
    type SetStateAction,
    useCallback,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';

type ProctoringAttempt = {
    id: number;
    access_token?: string | null;
    is_public?: boolean;
};

type FaceViolationType = 'no_face' | 'multiple_faces';

type MetadataValue = string | number | boolean | null;
type Metadata = Record<string, MetadataValue>;

type FaceMonitoringStatus =
    | 'idle'
    | 'loading'
    | 'active'
    | 'camera_unavailable'
    | 'model_error'
    | 'upload_error';

type FaceMonitoringCounts = {
    no_face: number;
    multiple_faces: number;
};

export type FaceMonitoringControls = {
    status: FaceMonitoringStatus;
    warning: string | null;
    counts: FaceMonitoringCounts;
    noFaceDurationSeconds: number;
};

const MEDIAPIPE_VERSION = '0.10.35';
const WASM_URL = `https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@${MEDIAPIPE_VERSION}/wasm`;
const FACE_MODEL_URL =
    'https://storage.googleapis.com/mediapipe-models/face_detector/blaze_face_short_range/float16/latest/blaze_face_short_range.tflite';
const SAMPLE_INTERVAL_MS = 700;
const CONFIRMATION_CHECKS = 3;
const WARNING_VISIBLE_MS = 5000;
const SNAPSHOT_MAX_WIDTH = 640;
const SNAPSHOT_QUALITY = 0.72;

let detectorPromise: Promise<FaceDetector> | null = null;

export function useFaceMonitoring(
    attempt: ProctoringAttempt,
    cameraStream: MediaStream | null,
    disabled = false,
): FaceMonitoringControls {
    const storeUrl = useMemo(() => faceViolationRoute(attempt), [attempt]);
    const disabledRef = useRef(disabled);
    const consecutiveRef = useRef<{
        type: FaceViolationType | null;
        checks: number;
    }>({ type: null, checks: 0 });
    const activeViolationRef = useRef<FaceViolationType | null>(null);
    const noFaceStartedAtRef = useRef<number | null>(null);
    const activeNoFaceSnapshotIdRef = useRef<number | null>(null);
    const uploadInProgressRef = useRef(false);
    const warningTimerRef = useRef<number | null>(null);
    const [status, setStatus] = useState<FaceMonitoringStatus>('idle');
    const [warning, setWarning] = useState<string | null>(null);
    const [counts, setCounts] = useState<FaceMonitoringCounts>({
        no_face: 0,
        multiple_faces: 0,
    });
    const [noFaceDurationSeconds, setNoFaceDurationSeconds] = useState(0);

    useEffect(() => {
        disabledRef.current = disabled;
    }, [disabled]);

    const showWarning = useCallback((violationType: FaceViolationType) => {
        const message =
            violationType === 'no_face'
                ? 'Face not visible. Please stay in front of the camera.'
                : 'Multiple faces detected. Only the candidate may remain in frame.';

        setWarning(message);

        if (warningTimerRef.current !== null) {
            window.clearTimeout(warningTimerRef.current);
        }

        warningTimerRef.current = window.setTimeout(() => {
            setWarning(null);
            warningTimerRef.current = null;
        }, WARNING_VISIBLE_MS);
    }, []);

    const uploadViolation = useCallback(
        async (
            violationType: FaceViolationType,
            faceCount: number,
            video: HTMLVideoElement,
            startedAtMs: number | null,
        ) => {
            if (uploadInProgressRef.current || disabledRef.current) {
                return;
            }

            uploadInProgressRef.current = true;

            try {
                const snapshot = await captureSnapshot(video);

                if (!snapshot) {
                    return;
                }

                const metadata = baseMetadata(video);
                const formData = new FormData();
                const now = Date.now();
                const effectiveStartedAtMs = startedAtMs ?? now;

                formData.append('violation_type', violationType);
                formData.append('face_count', String(faceCount));
                formData.append('captured_at', new Date(now).toISOString());
                formData.append(
                    'started_at',
                    new Date(effectiveStartedAtMs).toISOString(),
                );
                formData.append(
                    'duration_seconds',
                    String(secondsBetween(effectiveStartedAtMs, now)),
                );
                formData.append('snapshot', snapshot, `${violationType}.jpg`);

                Object.entries(metadata).forEach(([key, value]) => {
                    if (value !== null) {
                        formData.append(`metadata[${key}]`, String(value));
                    }
                });

                const response = await axios.post(storeUrl, formData, {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                const snapshotId = Number(response.data?.snapshot_id);

                if (
                    violationType === 'no_face' &&
                    Number.isFinite(snapshotId)
                ) {
                    if (noFaceStartedAtRef.current !== null) {
                        activeNoFaceSnapshotIdRef.current = snapshotId;
                    } else {
                        void updateNoFaceDuration(
                            attempt,
                            snapshotId,
                            effectiveStartedAtMs,
                            Date.now(),
                            setNoFaceDurationSeconds,
                            setStatus,
                        );
                    }
                }
            } catch {
                setStatus('upload_error');
            } finally {
                uploadInProgressRef.current = false;
            }
        },
        [attempt, storeUrl],
    );

    useEffect(() => {
        if (disabled) {
            setStatus('idle');
            return;
        }

        if (!cameraStream) {
            setStatus('camera_unavailable');
            return;
        }

        let stopped = false;
        let intervalId: number | null = null;
        const video = document.createElement('video');

        video.muted = true;
        video.playsInline = true;
        video.autoplay = true;
        video.srcObject = cameraStream;

        const start = async () => {
            setStatus('loading');

            try {
                await waitForVideo(video);
                const detector = await faceDetector();

                if (stopped) {
                    return;
                }

                setStatus('active');

                intervalId = window.setInterval(() => {
                    if (
                        stopped ||
                        disabledRef.current ||
                        video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA ||
                        video.videoWidth === 0
                    ) {
                        return;
                    }

                    try {
                        const result = detector.detectForVideo(
                            video,
                            performance.now(),
                        );
                        handleFaceCount(
                            result.detections.length,
                            video,
                            attempt,
                            uploadViolation,
                            showWarning,
                            setCounts,
                            setNoFaceDurationSeconds,
                            setStatus,
                            consecutiveRef,
                            activeViolationRef,
                            noFaceStartedAtRef,
                            activeNoFaceSnapshotIdRef,
                        );
                    } catch {
                        setStatus('model_error');
                    }
                }, SAMPLE_INTERVAL_MS);
            } catch {
                if (!stopped) {
                    setStatus('model_error');
                }
            }
        };

        void start();

        return () => {
            stopped = true;

            if (intervalId !== null) {
                window.clearInterval(intervalId);
            }

            video.pause();
            video.srcObject = null;
            if (
                activeNoFaceSnapshotIdRef.current !== null &&
                noFaceStartedAtRef.current !== null
            ) {
                void updateNoFaceDuration(
                    attempt,
                    activeNoFaceSnapshotIdRef.current,
                    noFaceStartedAtRef.current,
                    Date.now(),
                    setNoFaceDurationSeconds,
                    setStatus,
                );
            }
            consecutiveRef.current = { type: null, checks: 0 };
            activeViolationRef.current = null;
            noFaceStartedAtRef.current = null;
            activeNoFaceSnapshotIdRef.current = null;
        };
    }, [attempt, cameraStream, disabled, showWarning, uploadViolation]);

    useEffect(() => {
        return () => {
            if (warningTimerRef.current !== null) {
                window.clearTimeout(warningTimerRef.current);
            }
        };
    }, []);

    return {
        status,
        warning,
        counts,
        noFaceDurationSeconds,
    };
}

function handleFaceCount(
    faceCount: number,
    video: HTMLVideoElement,
    attempt: ProctoringAttempt,
    uploadViolation: (
        violationType: FaceViolationType,
        faceCount: number,
        video: HTMLVideoElement,
        startedAtMs: number | null,
    ) => Promise<void>,
    showWarning: (violationType: FaceViolationType) => void,
    setCounts: Dispatch<SetStateAction<FaceMonitoringCounts>>,
    setNoFaceDurationSeconds: Dispatch<SetStateAction<number>>,
    setStatus: Dispatch<SetStateAction<FaceMonitoringStatus>>,
    consecutiveRef: MutableRefObject<{
        type: FaceViolationType | null;
        checks: number;
    }>,
    activeViolationRef: MutableRefObject<FaceViolationType | null>,
    noFaceStartedAtRef: MutableRefObject<number | null>,
    activeNoFaceSnapshotIdRef: MutableRefObject<number | null>,
) {
    const violationType =
        faceCount === 0
            ? 'no_face'
            : faceCount > 1
              ? 'multiple_faces'
              : null;

    if (violationType === 'no_face' && noFaceStartedAtRef.current === null) {
        noFaceStartedAtRef.current = Date.now();
    }

    if (violationType !== 'no_face') {
        finalizeNoFaceIfNeeded(
            attempt,
            noFaceStartedAtRef,
            activeNoFaceSnapshotIdRef,
            setNoFaceDurationSeconds,
            setStatus,
        );
    }

    if (!violationType) {
        consecutiveRef.current = { type: null, checks: 0 };
        activeViolationRef.current = null;
        return;
    }

    if (consecutiveRef.current.type === violationType) {
        consecutiveRef.current.checks += 1;
    } else {
        consecutiveRef.current = { type: violationType, checks: 1 };
    }

    if (
        consecutiveRef.current.checks < CONFIRMATION_CHECKS ||
        activeViolationRef.current === violationType
    ) {
        return;
    }

    activeViolationRef.current = violationType;
    setCounts((current) => ({
        ...current,
        [violationType]: current[violationType] + 1,
    }));
    showWarning(violationType);
    void uploadViolation(
        violationType,
        faceCount,
        video,
        violationType === 'no_face' ? noFaceStartedAtRef.current : null,
    );
}

function finalizeNoFaceIfNeeded(
    attempt: ProctoringAttempt,
    noFaceStartedAtRef: MutableRefObject<number | null>,
    activeNoFaceSnapshotIdRef: MutableRefObject<number | null>,
    setNoFaceDurationSeconds: Dispatch<SetStateAction<number>>,
    setStatus: Dispatch<SetStateAction<FaceMonitoringStatus>>,
): void {
    const startedAtMs = noFaceStartedAtRef.current;
    const snapshotId = activeNoFaceSnapshotIdRef.current;

    noFaceStartedAtRef.current = null;
    activeNoFaceSnapshotIdRef.current = null;

    if (startedAtMs === null || snapshotId === null) {
        return;
    }

    void updateNoFaceDuration(
        attempt,
        snapshotId,
        startedAtMs,
        Date.now(),
        setNoFaceDurationSeconds,
        setStatus,
    );
}

async function updateNoFaceDuration(
    attempt: ProctoringAttempt,
    snapshotId: number,
    startedAtMs: number,
    endedAtMs: number,
    setNoFaceDurationSeconds: Dispatch<SetStateAction<number>>,
    setStatus: Dispatch<SetStateAction<FaceMonitoringStatus>>,
): Promise<void> {
    const durationSeconds = secondsBetween(startedAtMs, endedAtMs);

    if (durationSeconds <= 0) {
        return;
    }

    setNoFaceDurationSeconds((current) => current + durationSeconds);

    try {
        await axios.patch(
            faceViolationDurationRoute(attempt, snapshotId),
            {
                ended_at: new Date(endedAtMs).toISOString(),
                duration_seconds: durationSeconds,
                metadata: durationMetadata(),
            },
            {
                headers: {
                    Accept: 'application/json',
                },
            },
        );
    } catch {
        setStatus('upload_error');
    }
}

async function faceDetector(): Promise<FaceDetector> {
    detectorPromise ??= FilesetResolver.forVisionTasks(WASM_URL).then((vision) =>
        FaceDetector.createFromOptions(vision, {
            baseOptions: {
                modelAssetPath: FACE_MODEL_URL,
                delegate: 'CPU',
            },
            runningMode: 'VIDEO',
        }),
    );

    return detectorPromise;
}

async function waitForVideo(video: HTMLVideoElement): Promise<void> {
    try {
        await video.play();
    } catch {
        // The stream can still become readable through loadedmetadata.
    }

    if (video.videoWidth > 0 && video.readyState >= HTMLMediaElement.HAVE_METADATA) {
        return;
    }

    await new Promise<void>((resolve, reject) => {
        const timeout = window.setTimeout(() => {
            cleanup();
            reject(new Error('Camera video did not become ready.'));
        }, 5000);
        const cleanup = () => {
            window.clearTimeout(timeout);
            video.removeEventListener('loadedmetadata', handleLoadedMetadata);
        };
        const handleLoadedMetadata = () => {
            cleanup();
            resolve();
        };

        video.addEventListener('loadedmetadata', handleLoadedMetadata, {
            once: true,
        });
    });
}

async function captureSnapshot(video: HTMLVideoElement): Promise<Blob | null> {
    if (video.videoWidth === 0 || video.videoHeight === 0) {
        return null;
    }

    const scale = Math.min(1, SNAPSHOT_MAX_WIDTH / video.videoWidth);
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(video.videoWidth * scale));
    canvas.height = Math.max(1, Math.round(video.videoHeight * scale));

    const context = canvas.getContext('2d');

    if (!context) {
        return null;
    }

    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    return new Promise((resolve) => {
        canvas.toBlob(
            (blob) => resolve(blob),
            'image/jpeg',
            SNAPSHOT_QUALITY,
        );
    });
}

function faceViolationRoute(attempt: ProctoringAttempt): string {
    if (attempt.is_public && attempt.access_token) {
        return route(
            'candidate.public-attempts.face-proctoring-violations.store',
            attempt.access_token,
        );
    }

    return route('candidate.attempts.face-proctoring-violations.store', attempt.id);
}

function faceViolationDurationRoute(
    attempt: ProctoringAttempt,
    snapshotId: number,
): string {
    if (attempt.is_public && attempt.access_token) {
        return route(
            'candidate.public-attempts.face-proctoring-violations.duration.update',
            [attempt.access_token, snapshotId],
        );
    }

    return route('candidate.attempts.face-proctoring-violations.duration.update', [
        attempt.id,
        snapshotId,
    ]);
}

function baseMetadata(video: HTMLVideoElement): Metadata {
    return {
        video_width: video.videoWidth,
        video_height: video.videoHeight,
        screen_width: window.screen.width,
        screen_height: window.screen.height,
        visibility_state: document.visibilityState,
        fullscreen: Boolean(document.fullscreenElement),
        language: navigator.language,
    };
}

function durationMetadata(): Metadata {
    return {
        screen_width: window.screen.width,
        screen_height: window.screen.height,
        visibility_state: document.visibilityState,
        fullscreen: Boolean(document.fullscreenElement),
        language: navigator.language,
    };
}

function secondsBetween(startedAtMs: number, endedAtMs: number): number {
    return Math.max(0, Math.round((endedAtMs - startedAtMs) / 1000));
}
