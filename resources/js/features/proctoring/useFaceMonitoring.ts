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
    const uploadInProgressRef = useRef(false);
    const warningTimerRef = useRef<number | null>(null);
    const [status, setStatus] = useState<FaceMonitoringStatus>('idle');
    const [warning, setWarning] = useState<string | null>(null);
    const [counts, setCounts] = useState<FaceMonitoringCounts>({
        no_face: 0,
        multiple_faces: 0,
    });

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

                formData.append('violation_type', violationType);
                formData.append('face_count', String(faceCount));
                formData.append('captured_at', new Date().toISOString());
                formData.append('snapshot', snapshot, `${violationType}.jpg`);

                Object.entries(metadata).forEach(([key, value]) => {
                    if (value !== null) {
                        formData.append(`metadata[${key}]`, String(value));
                    }
                });

                await axios.post(storeUrl, formData, {
                    headers: {
                        Accept: 'application/json',
                    },
                });
            } catch {
                setStatus('upload_error');
            } finally {
                uploadInProgressRef.current = false;
            }
        },
        [storeUrl],
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
                            uploadViolation,
                            showWarning,
                            setCounts,
                            consecutiveRef,
                            activeViolationRef,
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
            consecutiveRef.current = { type: null, checks: 0 };
            activeViolationRef.current = null;
        };
    }, [cameraStream, disabled, showWarning, uploadViolation]);

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
    };
}

function handleFaceCount(
    faceCount: number,
    video: HTMLVideoElement,
    uploadViolation: (
        violationType: FaceViolationType,
        faceCount: number,
        video: HTMLVideoElement,
    ) => Promise<void>,
    showWarning: (violationType: FaceViolationType) => void,
    setCounts: Dispatch<SetStateAction<FaceMonitoringCounts>>,
    consecutiveRef: MutableRefObject<{
        type: FaceViolationType | null;
        checks: number;
    }>,
    activeViolationRef: MutableRefObject<FaceViolationType | null>,
) {
    const violationType =
        faceCount === 0
            ? 'no_face'
            : faceCount > 1
              ? 'multiple_faces'
              : null;

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
    void uploadViolation(violationType, faceCount, video);
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
