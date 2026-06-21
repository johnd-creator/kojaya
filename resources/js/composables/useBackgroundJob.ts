import axios from "axios";
import { computed, onBeforeUnmount, ref } from "vue";
import { enqueuePdf, pdfStatus as pdfStatusRoute } from "@/actions/App/Http/Controllers/Cooperative/PosReportController";

export type BackgroundJobPhase = "pending" | "processing" | "completed" | "failed";

export interface BackgroundJobState {
    jobId: string | null;
    status: BackgroundJobPhase;
    progress: number;
    errorMessage: string | null;
    originalName: string | null;
    fileSize: number | null;
    downloadUrl: string | null;
    finishedAt: string | null;
}

interface BackgroundJobTrackerOptions {
    pollIntervalMs?: number;
    timeoutMs?: number;
}

interface BackgroundJobResponse {
    job_id: string;
    status: BackgroundJobPhase;
    progress: number;
    error_message: string | null;
    file_size: number | null;
    original_name: string | null;
    download_url: string | null;
    started_at: string | null;
    finished_at: string | null;
}

const POLL_INTERVAL_DEFAULT = 1500;
const TIMEOUT_DEFAULT = 180_000;

export function useBackgroundJob(options: BackgroundJobTrackerOptions = {}) {
    const pollIntervalMs = options.pollIntervalMs ?? POLL_INTERVAL_DEFAULT;
    const timeoutMs = options.timeoutMs ?? TIMEOUT_DEFAULT;

    const state = ref<BackgroundJobState>({
        jobId: null,
        status: "pending",
        progress: 0,
        errorMessage: null,
        originalName: null,
        fileSize: null,
        downloadUrl: null,
        finishedAt: null,
    });

    const submitting = ref<boolean>(false);
    const error = ref<string | null>(null);

    let pollTimer: ReturnType<typeof setInterval> | null = null;
    let timeoutTimer: ReturnType<typeof setTimeout> | null = null;

    const hasJobStarted = computed<boolean>(
        () => state.value.jobId !== null || submitting.value,
    );

    function clearTimers(): void {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
        if (timeoutTimer) {
            clearTimeout(timeoutTimer);
            timeoutTimer = null;
        }
    }

    function applyResponse(payload: BackgroundJobResponse): void {
        state.value = {
            jobId: payload.job_id,
            status: payload.status,
            progress: payload.progress,
            errorMessage: payload.error_message,
            originalName: payload.original_name,
            fileSize: payload.file_size,
            downloadUrl: payload.download_url,
            finishedAt: payload.finished_at,
        };
    }

    function isTerminal(phase: BackgroundJobPhase): boolean {
        return phase === "completed" || phase === "failed";
    }

    async function poll(): Promise<void> {
        if (!state.value.jobId) {
            return;
        }

        try {
            const response = await axios.get<BackgroundJobResponse>(
                pdfStatusRoute({ job: state.value.jobId }).url,
            );
            applyResponse(response.data);

            if (isTerminal(response.data.status)) {
                clearTimers();
            }
        } catch (pollError) {
            const message = pollError instanceof Error ? pollError.message : "Polling gagal";
            error.value = message;
            if (state.value.status !== "processing") {
                clearTimers();
            }
        }
    }

    function startPolling(): void {
        clearTimers();
        pollTimer = setInterval(() => {
            void poll();
        }, pollIntervalMs);
        timeoutTimer = setTimeout(() => {
            clearTimers();
            if (!isTerminal(state.value.status)) {
                error.value = "Pekerjaan memakan waktu terlalu lama. Coba lagi.";
            }
        }, timeoutMs);
    }

    function reset(): void {
        clearTimers();
        state.value = {
            jobId: null,
            status: "pending",
            progress: 0,
            errorMessage: null,
            originalName: null,
            fileSize: null,
            downloadUrl: null,
            finishedAt: null,
        };
        error.value = null;
        submitting.value = false;
    }

    async function enqueue(payload: Record<string, unknown>): Promise<void> {
        reset();
        submitting.value = true;
        error.value = null;

        try {
            const response = await axios.post<BackgroundJobResponse>(
                enqueuePdf().url,
                payload,
                { params: payload },
            );
            applyResponse(response.data);
            startPolling();
        } catch (submitError) {
            const message = submitError instanceof Error ? submitError.message : "Enqueue gagal";
            error.value = message;
            state.value = {
                ...state.value,
                status: "failed",
                errorMessage: message,
                finishedAt: new Date().toISOString(),
            };
        } finally {
            submitting.value = false;
        }
    }

    onBeforeUnmount(() => {
        clearTimers();
    });

    return {
        state,
        submitting,
        error,
        hasJobStarted,
        enqueue,
        reset,
        poll,
    };
}
