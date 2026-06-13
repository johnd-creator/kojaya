import { usePage, router } from "@inertiajs/vue3";
import axios from "axios";
import { ref, watch } from "vue";

interface QueuedItem {
    idempotency_key: string;
    endpoint: string;
    method: "POST" | "PUT" | "DELETE";
    payload: Record<string, unknown>;
    created_at: string;
    status: "PENDING" | "SYNCED" | "FAILED";
    last_error?: string;
    synced_at?: string;
    server_response?: Record<string, unknown>;
}

const STORAGE_KEY = "pos-offline-queue:v1";
const CATALOG_KEY = "pos-offline-catalog:v1";
const isOnline = ref<boolean>(typeof navigator !== "undefined" ? navigator.onLine : true);
const queue = ref<QueuedItem[]>(loadQueue());
const syncing = ref<boolean>(false);
const lastSyncAt = ref<string | null>(null);
const lastError = ref<string | null>(null);

function loadQueue(): QueuedItem[] {
    if (typeof localStorage === "undefined") return [];
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        return raw ? (JSON.parse(raw) as QueuedItem[]) : [];
    } catch {
        return [];
    }
}

function persist(): void {
    if (typeof localStorage === "undefined") return;
    localStorage.setItem(STORAGE_KEY, JSON.stringify(queue.value));
}

export function useOfflinePos() {
    if (typeof window !== "undefined") {
        window.addEventListener("online", () => {
            isOnline.value = true;
            void flush();
        });
        window.addEventListener("offline", () => {
            isOnline.value = false;
        });
    }

    async function fetchCatalog(): Promise<void> {
        try {
            const page = usePage();
            const token = (page.props as Record<string, unknown>).auth
                ? ((page.props as Record<string, unknown>).auth as Record<string, unknown>).token
                : undefined;
            const { data } = await axios.get("/api/v1/pos/sync/catalog", {
                headers: token ? { Authorization: `Bearer ${token}` } : {},
            });
            if (typeof localStorage !== "undefined") {
                localStorage.setItem(CATALOG_KEY, JSON.stringify(data));
            }
            lastSyncAt.value = new Date().toISOString();
        } catch (e) {
            lastError.value = (e as Error).message;
        }
    }

    function readCatalog<T = unknown>(): T | null {
        if (typeof localStorage === "undefined") return null;
        const raw = localStorage.getItem(CATALOG_KEY);
        return raw ? (JSON.parse(raw) as T) : null;
    }

    function enqueue(endpoint: string, method: "POST" | "PUT" | "DELETE", payload: Record<string, unknown>): string {
        const idempotency_key =
            (payload.idempotency_key as string) ||
            (typeof crypto !== "undefined" && "randomUUID" in crypto
                ? crypto.randomUUID()
                : `${Date.now()}-${Math.random().toString(36).slice(2)}`);

        queue.value.push({
            idempotency_key,
            endpoint,
            method,
            payload,
            created_at: new Date().toISOString(),
            status: "PENDING",
        });
        persist();

        if (isOnline.value) {
            void flush();
        }

        return idempotency_key;
    }

    async function flush(): Promise<void> {
        if (syncing.value) return;
        if (!isOnline.value) return;
        syncing.value = true;
        lastError.value = null;

        const pending = queue.value.filter((q) => q.status === "PENDING");
        if (pending.length === 0) {
            syncing.value = false;
            return;
        }

        try {
            const page = usePage();
            const token = (page.props as Record<string, unknown>).auth
                ? ((page.props as Record<string, unknown>).auth as Record<string, unknown>).token
                : undefined;
            const headers: Record<string, string> = {};
            if (token) headers.Authorization = `Bearer ${token}`;

            const { data } = await axios.post(
                "/api/v1/pos/sync/batch",
                { idempotency_keys: pending.map((q) => q.idempotency_key) },
                { headers },
            );

            const results = (data.data as Array<Record<string, unknown>>) ?? [];
            for (const result of results) {
                const key = result.idempotency_key as string;
                const status = result.status as number;
                const item = queue.value.find((q) => q.idempotency_key === key);
                if (!item) continue;
                if (status >= 200 && status < 300) {
                    item.status = "SYNCED";
                    item.synced_at = new Date().toISOString();
                    item.server_response = result.data as Record<string, unknown>;
                } else {
                    item.status = "FAILED";
                    item.last_error = JSON.stringify(result.data);
                }
            }
            lastSyncAt.value = new Date().toISOString();
        } catch (e) {
            lastError.value = (e as Error).message;
        } finally {
            persist();
            syncing.value = false;
        }
    }

    function clearSynced(): void {
        queue.value = queue.value.filter((q) => q.status !== "SYNCED");
        persist();
    }

    function pendingCount(): number {
        return queue.value.filter((q) => q.status === "PENDING").length;
    }

    function failedCount(): number {
        return queue.value.filter((q) => q.status === "FAILED").length;
    }

    watch(
        queue,
        () => persist(),
        { deep: true },
    );

    return {
        isOnline,
        syncing,
        queue,
        lastSyncAt,
        lastError,
        enqueue,
        flush,
        fetchCatalog,
        readCatalog,
        clearSynced,
        pendingCount,
        failedCount,
    };
}
