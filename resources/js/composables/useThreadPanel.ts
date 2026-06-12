import { router, usePage } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import type { Message } from '@/types/app/Models/Message';

/**
 * Drives the thread side panel and keeps its state in the URL
 * (?thread={parentId}&msg={replyId}) so threads — and specific replies —
 * are shareable and survive refresh/back/forward navigation.
 */
export function useThreadPanel() {
    const page = usePage();

    const open = ref(false);
    const message = ref<Message | null>(null);
    const highlightId = ref<number | null>(null);

    function positiveIntParam(name: string): number | null {
        const queryString = page.url.split('?')[1] ?? '';
        const value = new URLSearchParams(queryString).get(name);
        const id = value ? Number(value) : NaN;

        return Number.isInteger(id) && id > 0 ? id : null;
    }

    function setThreadParams(id: number | null, msgId: number | null): void {
        const [path, queryString] = page.url.split('?');
        const params = new URLSearchParams(queryString ?? '');

        if (id) {
            params.set('thread', String(id));
        } else {
            params.delete('thread');
        }

        if (id && msgId) {
            params.set('msg', String(msgId));
        } else {
            params.delete('msg');
        }

        const url = params.size > 0 ? `${path}?${params.toString()}` : path;

        if (url !== page.url) {
            router.push({ url, preserveScroll: true, preserveState: true });
        }
    }

    async function loadMessage(id: number): Promise<void> {
        const response = await fetch(`/api/messages/${id}`);

        if (response.ok) {
            message.value = (await response.json()) as Message;
            open.value = true;
        }
    }

    function syncFromUrl(): void {
        const id = positiveIntParam('thread');

        if (!id) {
            open.value = false;
            return;
        }

        highlightId.value = positiveIntParam('msg');

        if (message.value?.id === id) {
            open.value = true;
            return;
        }

        void loadMessage(id);
    }

    function openThread(payload: {
        message: Message;
        highlightId: number | null;
    }): void {
        message.value = payload.message;
        highlightId.value = payload.highlightId;
        open.value = true;
        setThreadParams(payload.message.id, payload.highlightId);
    }

    function closeThread(): void {
        open.value = false;
        highlightId.value = null;
        setThreadParams(null, null);
    }

    onMounted(syncFromUrl);

    watch(
        () => page.url,
        () => syncFromUrl(),
    );

    return { open, message, highlightId, openThread, closeThread };
}
