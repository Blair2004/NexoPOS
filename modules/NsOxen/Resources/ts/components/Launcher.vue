<script setup lang="ts">
import DOMPurify from 'dompurify';
import { marked, Renderer } from 'marked';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { __m } from '../i18n';
import ToolsPopup from './ToolsPopup.vue';

declare const nsHttpClient: any;
declare const nsSnackBar: any;
declare const Popup: any;
declare const nsConfirmPopup: any;
declare const ns: any;

type Workspace = 'chat' | 'history';
type LauncherEdge = 'left' | 'right' | 'top' | 'bottom';
type LauncherPreference = {
    icon: { edge: LauncherEdge; offset_ratio: number };
    popup: { x_ratio: number; y_ratio: number };
};
type Point = { x: number; y: number };
type PointerDrag = { pointerId: number; startPointer: Point; startPosition: Point; dragged: boolean };
type OxenAction = { id: string; title: string; tool: string; risk: string; requires_confirmation: boolean; status: string; summary?: string };
type ActionEvent = { proposal_id: string; decision: 'approved' | 'rejected'; tool: string };
type ToolActivity = { event: 'tool.started' | 'tool.completed' | 'tool.failed'; data: { tool: string; call_id?: string; summary?: string; code?: string } };
type OxenAttachment = { id: string; name: string; mime_type: string; size: number };
type OxenMessage = { type?: 'info'; role: 'user' | 'assistant'; content: string; status?: 'completed' | 'failed'; metadata?: { type?: 'info'; actions?: OxenAction[]; activity?: ToolActivity[]; attachments?: OxenAttachment[]; error?: { code: string }; action_event?: ActionEvent }; actions?: OxenAction[]; activity?: ToolActivity[] };

const ICON_SIZE = 56;
const SAFE_MARGIN = 12;
const DRAG_THRESHOLD = 6;
const DEFAULT_PREFERENCE: LauncherPreference = {
    icon: { edge: 'right', offset_ratio: 0.82 },
    popup: { x_ratio: 1, y_ratio: 1 },
};

const open = ref(sessionStorage.getItem('ns-oxen-open') === 'yes');
const draft = ref(sessionStorage.getItem('ns-oxen-draft') ?? '');
const configured = ref(false);
const canManage = ref(false);
const conversationId = ref<string | null>(sessionStorage.getItem('ns-oxen-conversation'));
const messages = ref<OxenMessage[]>([]);
const conversations = ref<any[]>([]);
const tools = ref<any[]>([]);
const workspace = ref<Workspace>('chat');
const streaming = ref(false);
const thinking = ref(false);
const attachments = ref<File[]>([]);
const theme = ref(ns.theme);
const attachmentInput = ref<HTMLInputElement | null>(null);
const thinkingCharacters = Array.from('Thinking…');
const messageList = ref<HTMLElement | null>(null);
const composer = ref<HTMLTextAreaElement | null>(null);
const preference = ref<LauncherPreference>(structuredClone(DEFAULT_PREFERENCE));
const viewport = ref({ width: window.innerWidth, height: window.innerHeight });
const iconDragPosition = ref<Point | null>(null);
const popupDragPosition = ref<Point | null>(null);
const activityClock = ref(Date.now());
const launcherIsLoading = ref(true);
const historyIsLoading = ref(false);
const discussionIsLoading = ref(false);
let controller: AbortController | null = null;
let iconDrag: PointerDrag | null = null;
let popupDrag: PointerDrag | null = null;
let suppressIconClick = false;
let activityTimer: number | null = null;

const iconPosition = computed<Point>(() => iconDragPosition.value ?? positionedIcon(preference.value.icon));
const popupPosition = computed<Point>(() => popupDragPosition.value ?? positionedPopup(preference.value.popup));
const popupSize = computed(() => ({
    width: viewport.value.width < 640 ? viewport.value.width : Math.min(480, Math.max(0, viewport.value.width - SAFE_MARGIN * 2)),
    height: viewport.value.width < 640 ? viewport.value.height : Math.min(720, Math.max(0, viewport.value.height - SAFE_MARGIN * 2)),
}));
const iconStyle = computed(() => ({ left: `${iconPosition.value.x}px`, top: `${iconPosition.value.y}px` }));
const popupStyle = computed(() => ({
    left: `${popupPosition.value.x}px`,
    top: `${popupPosition.value.y}px`,
    width: `${popupSize.value.width}px`,
    height: `${popupSize.value.height}px`,
}));
const componentIsLoading = computed(() => launcherIsLoading.value
    || (workspace.value === 'history' ? historyIsLoading.value : discussionIsLoading.value));

const renderer = new Renderer();
renderer.html = ({ text }: any) => escapeHtml(text ?? '');

DOMPurify.addHook('afterSanitizeAttributes', node => {
    if (node instanceof HTMLAnchorElement) {
        node.setAttribute('target', '_blank');
        node.setAttribute('rel', 'noopener noreferrer');
    }
});

const api = (method: string, url: string, data?: any) => new Promise<any>((resolve, reject) => nsHttpClient[method](url, data).subscribe({ next: resolve, error: reject }));

function escapeHtml(value: string): string {
    const element = document.createElement('div');
    element.textContent = value;
    return element.innerHTML;
}

function renderMarkdown(markdown: string): string {
    const html = marked.parse(markdown, { async: false, renderer }) as string;
    return DOMPurify.sanitize(html, {
        ALLOWED_URI_REGEXP: /^(?:(?:https?|mailto):|[^a-z]|[a-z+.-]+(?:[^a-z+.-:]|$))/i,
        FORBID_TAGS: ['style', 'iframe', 'object', 'embed', 'form'],
    });
}

function toolTitle(name: string): string {
    return tools.value.find(tool => tool.name === name)?.title ?? name.replaceAll('_', ' ');
}

function recordActivity(message: OxenMessage, activity: ToolActivity): void {
    message.activity ??= [];
    mergeActivity(message.activity, activity);
    scrollToLatest('auto');
}

function mergeActivity(activities: ToolActivity[], activity: ToolActivity): void {
    const index = activities.findIndex(item => item.data.call_id && item.data.call_id === activity.data.call_id);
    if (index >= 0) {
        activities[index] = activity;
    } else {
        activities.push(activity);
    }
}

function normalizeActivities(activities: ToolActivity[]): ToolActivity[] {
    return activities.reduce<ToolActivity[]>((normalized, activity) => {
        mergeActivity(normalized, activity);

        return normalized;
    }, []);
}

function clamp(value: number, minimum: number, maximum: number): number {
    return Math.min(Math.max(value, minimum), Math.max(minimum, maximum));
}

function normalizeRatio(value: unknown, fallback: number): number {
    const ratio = typeof value === 'number' ? value : Number(value);

    return Number.isFinite(ratio) && ratio >= 0 && ratio <= 1 ? ratio : fallback;
}

function normalizePreference(value: any): LauncherPreference {
    const iconPreference = value?.icon && typeof value.icon === 'object' ? value.icon : value;
    const popupPreference = value?.popup && typeof value.popup === 'object' ? value.popup : {};
    const edge = ['left', 'right', 'top', 'bottom'].includes(iconPreference?.edge)
        ? iconPreference.edge as LauncherEdge
        : DEFAULT_PREFERENCE.icon.edge;

    return {
        icon: {
            edge,
            offset_ratio: normalizeRatio(iconPreference?.offset_ratio, DEFAULT_PREFERENCE.icon.offset_ratio),
        },
        popup: {
            x_ratio: normalizeRatio(popupPreference?.x_ratio, DEFAULT_PREFERENCE.popup.x_ratio),
            y_ratio: normalizeRatio(popupPreference?.y_ratio, DEFAULT_PREFERENCE.popup.y_ratio),
        },
    };
}

function positionedIcon(iconPreference: LauncherPreference['icon']): Point {
    const maximumX = Math.max(SAFE_MARGIN, viewport.value.width - ICON_SIZE - SAFE_MARGIN);
    const maximumY = Math.max(SAFE_MARGIN, viewport.value.height - ICON_SIZE - SAFE_MARGIN);
    const horizontalTravel = Math.max(0, maximumX - SAFE_MARGIN);
    const verticalTravel = Math.max(0, maximumY - SAFE_MARGIN);

    if (iconPreference.edge === 'left' || iconPreference.edge === 'right') {
        return {
            x: iconPreference.edge === 'left' ? SAFE_MARGIN : maximumX,
            y: SAFE_MARGIN + verticalTravel * iconPreference.offset_ratio,
        };
    }

    return {
        x: SAFE_MARGIN + horizontalTravel * iconPreference.offset_ratio,
        y: iconPreference.edge === 'top' ? SAFE_MARGIN : maximumY,
    };
}

function positionedPopup(popupPreference: LauncherPreference['popup']): Point {
    if (viewport.value.width < 640) {
        return { x: 0, y: 0 };
    }

    const horizontalTravel = Math.max(0, viewport.value.width - popupSize.value.width - SAFE_MARGIN * 2);
    const verticalTravel = Math.max(0, viewport.value.height - popupSize.value.height - SAFE_MARGIN * 2);

    return {
        x: SAFE_MARGIN + horizontalTravel * popupPreference.x_ratio,
        y: SAFE_MARGIN + verticalTravel * popupPreference.y_ratio,
    };
}

function toggle(): void {
    const updateLauncherState = (): void => {
        open.value = !open.value;
        sessionStorage.setItem('ns-oxen-open', open.value ? 'yes' : 'no');
    };
    const transitionDocument = document as Document & {
        startViewTransition?: (callback: () => void) => void;
    };

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !transitionDocument.startViewTransition) {
        updateLauncherState();

        return;
    }

    transitionDocument.startViewTransition(updateLauncherState);
}

async function persistPreference(): Promise<void> {
    try {
        await api('put', '/api/oxen/launcher-preference', {
            icon: { ...preference.value.icon },
            popup: { ...preference.value.popup },
        });
    } catch (error: any) {
        nsSnackBar.error(actionError(error, __m('Unable to save the Oxen position.', 'NsOxen')).message);
    }
}

function iconPointerDown(event: PointerEvent): void {
    if (event.button !== 0) {
        return;
    }

    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
    iconDrag = {
        pointerId: event.pointerId,
        startPointer: { x: event.clientX, y: event.clientY },
        startPosition: { ...iconPosition.value },
        dragged: false,
    };
}

function iconPointerMove(event: PointerEvent): void {
    if (!iconDrag || iconDrag.pointerId !== event.pointerId) {
        return;
    }

    const deltaX = event.clientX - iconDrag.startPointer.x;
    const deltaY = event.clientY - iconDrag.startPointer.y;
    iconDrag.dragged ||= Math.hypot(deltaX, deltaY) >= DRAG_THRESHOLD;
    if (!iconDrag.dragged) {
        return;
    }

    event.preventDefault();
    iconDragPosition.value = {
        x: clamp(iconDrag.startPosition.x + deltaX, SAFE_MARGIN, viewport.value.width - ICON_SIZE - SAFE_MARGIN),
        y: clamp(iconDrag.startPosition.y + deltaY, SAFE_MARGIN, viewport.value.height - ICON_SIZE - SAFE_MARGIN),
    };
}

function iconPointerUp(event: PointerEvent): void {
    if (!iconDrag || iconDrag.pointerId !== event.pointerId) {
        return;
    }

    if (iconDrag.dragged && iconDragPosition.value) {
        const position = iconDragPosition.value;
        const maximumX = Math.max(SAFE_MARGIN, viewport.value.width - ICON_SIZE - SAFE_MARGIN);
        const maximumY = Math.max(SAFE_MARGIN, viewport.value.height - ICON_SIZE - SAFE_MARGIN);
        const distances: Record<LauncherEdge, number> = {
            left: position.x - SAFE_MARGIN,
            right: maximumX - position.x,
            top: position.y - SAFE_MARGIN,
            bottom: maximumY - position.y,
        };
        const edge = (Object.keys(distances) as LauncherEdge[]).reduce((nearest, candidate) => distances[candidate] < distances[nearest] ? candidate : nearest);
        const horizontalTravel = Math.max(0, maximumX - SAFE_MARGIN);
        const verticalTravel = Math.max(0, maximumY - SAFE_MARGIN);
        const offset = edge === 'left' || edge === 'right'
            ? (position.y - SAFE_MARGIN) / Math.max(1, verticalTravel)
            : (position.x - SAFE_MARGIN) / Math.max(1, horizontalTravel);

        preference.value.icon = { edge, offset_ratio: clamp(offset, 0, 1) };
        suppressIconClick = true;
        void persistPreference();
    }

    iconDragPosition.value = null;
    iconDrag = null;
}

function iconPointerCancel(event: PointerEvent): void {
    if (iconDrag?.pointerId === event.pointerId) {
        iconDragPosition.value = null;
        iconDrag = null;
    }
}

function iconClick(): void {
    if (suppressIconClick) {
        suppressIconClick = false;
        return;
    }

    void toggle();
}

function popupPointerDown(event: PointerEvent): void {
    if (viewport.value.width < 640 || event.button !== 0) {
        return;
    }

    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
    popupDrag = {
        pointerId: event.pointerId,
        startPointer: { x: event.clientX, y: event.clientY },
        startPosition: { ...popupPosition.value },
        dragged: false,
    };
}

function popupPointerMove(event: PointerEvent): void {
    if (!popupDrag || popupDrag.pointerId !== event.pointerId) {
        return;
    }

    const deltaX = event.clientX - popupDrag.startPointer.x;
    const deltaY = event.clientY - popupDrag.startPointer.y;
    popupDrag.dragged ||= Math.hypot(deltaX, deltaY) >= DRAG_THRESHOLD;
    if (!popupDrag.dragged) {
        return;
    }

    event.preventDefault();
    popupDragPosition.value = {
        x: clamp(popupDrag.startPosition.x + deltaX, SAFE_MARGIN, viewport.value.width - popupSize.value.width - SAFE_MARGIN),
        y: clamp(popupDrag.startPosition.y + deltaY, SAFE_MARGIN, viewport.value.height - popupSize.value.height - SAFE_MARGIN),
    };
}

function popupPointerUp(event: PointerEvent): void {
    if (!popupDrag || popupDrag.pointerId !== event.pointerId) {
        return;
    }

    if (popupDrag.dragged && popupDragPosition.value) {
        const horizontalTravel = Math.max(0, viewport.value.width - popupSize.value.width - SAFE_MARGIN * 2);
        const verticalTravel = Math.max(0, viewport.value.height - popupSize.value.height - SAFE_MARGIN * 2);
        preference.value.popup = {
            x_ratio: clamp((popupDragPosition.value.x - SAFE_MARGIN) / Math.max(1, horizontalTravel), 0, 1),
            y_ratio: clamp((popupDragPosition.value.y - SAFE_MARGIN) / Math.max(1, verticalTravel), 0, 1),
        };
        void persistPreference();
    }

    popupDragPosition.value = null;
    popupDrag = null;
}

function popupPointerCancel(event: PointerEvent): void {
    if (popupDrag?.pointerId === event.pointerId) {
        popupDragPosition.value = null;
        popupDrag = null;
    }
}

function viewportResized(): void {
    viewport.value = { width: window.innerWidth, height: window.innerHeight };
    iconDragPosition.value = null;
    popupDragPosition.value = null;
    iconDrag = null;
    popupDrag = null;
}

function relativeActivity(value: string): string {
    activityClock.value;
    const activity = new Date(value).getTime();
    const elapsedSeconds = Number.isFinite(activity) ? Math.max(0, Math.floor((Date.now() - activity) / 1000)) : 0;
    const intervals: Array<{ unit: Intl.RelativeTimeFormatUnit; seconds: number }> = [
        { unit: 'year', seconds: 31_536_000 },
        { unit: 'month', seconds: 2_592_000 },
        { unit: 'week', seconds: 604_800 },
        { unit: 'day', seconds: 86_400 },
        { unit: 'hour', seconds: 3_600 },
        { unit: 'minute', seconds: 60 },
    ];
    const interval = intervals.find(candidate => elapsedSeconds >= candidate.seconds) ?? intervals.at(-1)!;
    const count = Math.max(1, Math.floor(elapsedSeconds / interval.seconds));
    const locale = document.documentElement.lang || navigator.language;

    return new Intl.RelativeTimeFormat(locale, { numeric: 'always' }).format(-count, interval.unit);
}

async function loadConversations(): Promise<void> {
    historyIsLoading.value = true;

    try {
        conversations.value = await api('get', '/api/oxen/conversations');
        if (!conversationId.value && conversations.value[0]) {
            conversationId.value = conversations.value[0].public_id;
        }
        if (conversationId.value) {
            await loadConversation(conversationId.value);
        }
    } catch (error: any) {
        nsSnackBar.error(actionError(error, __m('Unable to load conversation history.', 'NsOxen')).message);
    } finally {
        historyIsLoading.value = false;
    }
}

async function loadConversation(id: string): Promise<void> {
    discussionIsLoading.value = true;
    workspace.value = 'chat';

    try {
        const data = await api('get', `/api/oxen/conversations/${id}`);
        conversationId.value = id;
        sessionStorage.setItem('ns-oxen-conversation', id);
        messages.value = (data.messages ?? []).map((message: OxenMessage) => ({
            ...message,
            type: message.type === 'info' || message.metadata?.action_event ? 'info' : undefined,
            actions: message.metadata?.actions ?? [],
            activity: normalizeActivities(message.metadata?.activity ?? []),
        }));
        scrollToLatest('auto');
    } catch (error: any) {
        nsSnackBar.error(actionError(error, __m('Unable to load this discussion.', 'NsOxen')).message);
    } finally {
        discussionIsLoading.value = false;
    }
}

async function ensureConversation(): Promise<void> {
    if (conversationId.value) {
        return;
    }
    const conversation = await api('post', '/api/oxen/conversations', {});
    conversationId.value = conversation.public_id;
    sessionStorage.setItem('ns-oxen-conversation', conversation.public_id);
    conversations.value = [conversation, ...conversations.value];
}

function newConversation(): void {
    conversationId.value = null;
    messages.value = [];
    sessionStorage.removeItem('ns-oxen-conversation');
    workspace.value = 'chat';
}

function onAttachments(event: Event): void {
    attachments.value = Array.from((event.target as HTMLInputElement).files ?? []).slice(0, 4);
}

function openTools(): void {
    Popup.show(ToolsPopup, { tools: tools.value });
}

function actionError(error: any, fallback: string): { code?: string; message: string } {
    const payload = error?.error ?? error?.response?.data?.error ?? error?.response?.data ?? error;

    return {
        code: typeof payload?.code === 'string' ? payload.code : undefined,
        message: typeof payload?.message === 'string' ? payload.message : fallback,
    };
}

async function executeAction(action: OxenAction): Promise<void> {
    const run = async () => {
        try {
            await api('post', `/api/oxen/actions/${action.id}/execute`, {});
            action.status = 'executed';
            messages.value.push({
                type: 'info',
                role: 'assistant',
                content: __m('Action approved and completed:', 'NsOxen') + ' ' + action.title + '.',
                metadata: { type: 'info', action_event: { proposal_id: action.id, decision: 'approved', tool: action.tool } },
            });
            nsSnackBar.success(__m('Action completed.', 'NsOxen'));
        } catch (error: any) {
            const failure = actionError(error, __m('Unable to execute this action.', 'NsOxen'));
            if (failure.code === 'EXPIRED') action.status = 'expired';
            nsSnackBar.error(failure.message);
        }
    };

    if (action.requires_confirmation) {
        Popup.show(nsConfirmPopup, {
            title: __m('Confirm action', 'NsOxen'),
            message: __m('This action can change store data and cannot be silently undone. Continue?', 'NsOxen'),
            onAction: (confirmed: boolean) => confirmed && void run(),
        });
    } else {
        await run();
    }
}

async function retryAction(action: OxenAction): Promise<void> {
    try {
        const replacement = await api('post', '/api/oxen/actions/' + action.id + '/retry', {});
        Object.assign(action, replacement);
    } catch (error: any) {
        const failure = actionError(error, __m('Unable to retry this action.', 'NsOxen'));
        nsSnackBar.error(failure.message);
    }
}

async function rejectAction(action: OxenAction): Promise<void> {
    try {
        await api('post', `/api/oxen/actions/${action.id}/reject`, {});
        action.status = 'rejected';
        messages.value.push({
            type: 'info',
            role: 'assistant',
            content: __m('Action rejected. No changes were made:', 'NsOxen') + ' ' + action.title + '.',
            metadata: { type: 'info', action_event: { proposal_id: action.id, decision: 'rejected', tool: action.tool } },
        });
    } catch (error: any) {
        const failure = actionError(error, __m('Unable to reject this action.', 'NsOxen'));
        if (failure.code === 'EXPIRED') action.status = 'expired';
        nsSnackBar.error(failure.message);
    }
}

async function send(): Promise<void> {
    if ((!draft.value.trim() && attachments.value.length === 0) || streaming.value) {
        return;
    }
    await ensureConversation();
    const text = draft.value;
    const selectedAttachments = [...attachments.value];
    void nextTick(() => { resizeComposer(); scrollToLatest(); });
    const provisionalIndex = messages.value.length;
    messages.value.push({
        role: 'user',
        content: text,
        metadata: {
            attachments: selectedAttachments.map((file, index) => ({ id: `pending-${index}`, name: file.name, mime_type: file.type, size: file.size })),
        },
    });
    messages.value.push({ role: 'assistant', content: '', actions: [], activity: [] });
    const assistant = messages.value[messages.value.length - 1];
    scrollToLatest();
    streaming.value = true;
    thinking.value = true;
    controller = new AbortController();
    let messageAccepted = false;

    try {
        const csrf = await api('get', '/api/oxen/csrf-token');
        const payload = new FormData();
        payload.append('message', text);
        const routeName = (window as any).ns?.currentRouteName;
        if (typeof routeName === 'string' && routeName !== '') payload.append('route_name', routeName);
        selectedAttachments.forEach(file => payload.append('attachments[]', file, file.name));
        const response = await fetch(`/api/oxen/conversations/${conversationId.value}/messages`, {
            method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': csrf.token, Accept: 'text/event-stream' },
            body: payload, signal: controller.signal,
        });
        if (!response.ok || !response.body) {
            const failure = await response.json().catch(() => ({}));
            const validationMessage = Object.values(failure.errors ?? {}).flat().find(value => typeof value === 'string');
            throw new Error(validationMessage ?? failure.message ?? __m('Assistant request failed.', 'NsOxen'));
        }
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        while (true) {
            const { value, done } = await reader.read();
            if (done) break;
            buffer += decoder.decode(value, { stream: true });
            const parts = buffer.split('\n\n');
            buffer = parts.pop() ?? '';
            for (const part of parts) {
                const event = part.match(/^event: (.+)$/m)?.[1];
                const data = JSON.parse(part.match(/^data: (.+)$/m)?.[1] ?? '{}');
                if (event === 'text.delta') {
                    thinking.value = false;
                    assistant.content += data.delta;
                    scrollToLatest('auto');
                } else if (event === 'actions.available') {
                    assistant.actions = data.actions;
                } else if (event === 'message.accepted') {
                    messageAccepted = true;
                    messages.value[provisionalIndex].metadata = { attachments: data.attachments ?? [] };
                    draft.value = '';
                    attachments.value = [];
                    if (attachmentInput.value) attachmentInput.value.value = '';
                    sessionStorage.removeItem('ns-oxen-draft');
                    void nextTick(resizeComposer);
                } else if (event === 'tool.started' || event === 'tool.completed' || event === 'tool.failed') {
                    recordActivity(assistant, { event, data });
                } else if (event === 'conversation.updated') {
                    const conversation = conversations.value.find(item => item.public_id === data.conversation_id);
                    if (conversation) conversation.title = data.title;
                } else if (event === 'turn.failed') {
                    thinking.value = false;
                    assistant.status = 'failed';
                    assistant.metadata = { ...assistant.metadata, error: { code: data.code } };
                    if (!assistant.content) assistant.content = data.message;
                }
            }
        }
    } catch (error: any) {
        if (error.name === 'AbortError' && !messageAccepted) {
            messages.value.splice(provisionalIndex, 2);
        } else if (error.name !== 'AbortError') {
            const message = error.message ?? __m('Assistant request failed.', 'NsOxen');
            if (messageAccepted) {
                assistant.content = message;
                assistant.status = 'failed';
            } else {
                messages.value.splice(provisionalIndex, 2);
            }
            nsSnackBar.error(message);
        }
    } finally {
        streaming.value = false;
        thinking.value = false;
        controller = null;
    }
}

function composerKeydown(event: KeyboardEvent): void {
    if (event.key === 'Enter' && !event.shiftKey && !event.altKey && !event.isComposing) {
        event.preventDefault();
        void send();
    }
}

function scrollToLatest(behavior: ScrollBehavior = 'smooth'): void {
    void nextTick(() => {
        if (messageList.value) messageList.value.scrollTo({ top: messageList.value.scrollHeight, behavior });
    });
}

function resizeComposer(): void {
    if (!composer.value) return;
    composer.value.style.height = 'auto';
    const maxHeight = 400;
    const height = Math.min(composer.value.scrollHeight, maxHeight);
    composer.value.style.height = height + 'px';
    composer.value.style.overflowY = composer.value.scrollHeight > maxHeight ? 'auto' : 'hidden';
}

function persistDraft(): void {
    sessionStorage.setItem('ns-oxen-draft', draft.value);
}

function globalKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape' && open.value) toggle();
}

async function initializeLauncher(): Promise<void> {
    try {
        const data = await api('get', '/api/oxen/bootstrap');
        configured.value = data.configured && data.enabled;
        canManage.value = data.can_manage;
        preference.value = normalizePreference(data.preference);
        if (configured.value) {
            [tools.value] = await Promise.all([api('get', '/api/oxen/tools'), loadConversations()]);
        }
    } catch (error: any) {
        nsSnackBar.error(actionError(error, __m('Unable to load Oxen.', 'NsOxen')).message);
    } finally {
        launcherIsLoading.value = false;
    }
}

onMounted(() => {
    void initializeLauncher();
    activityTimer = window.setInterval(() => { activityClock.value = Date.now(); }, 60_000);
    window.addEventListener('resize', viewportResized);
    document.addEventListener('keydown', globalKeydown);
});

onBeforeUnmount(() => {
    controller?.abort();
    if (activityTimer !== null) window.clearInterval(activityTimer);
    window.removeEventListener('resize', viewportResized);
    document.removeEventListener('keydown', globalKeydown);
});
</script>

<template>
    <div class="ox:fixed ox:inset-0 ox:z-20 ox:pointer-events-none">
        <button v-show="!open" data-testid="oxen-launcher-icon" type="button" class="ns-oxen-launcher-morph ns-oxen-motion ox:pointer-events-auto ox:absolute ox:flex ox:h-14 ox:w-14 ox:touch-none ox:cursor-grab ox:items-center ox:justify-center ox:rounded-[20px] ox:bg-box-background ox:text-fontcolor ox:shadow-lg ox:hover:bg-box-elevation-hover ox:focus:outline-2 ox:focus:outline-primary ox:active:cursor-grabbing" :style="iconStyle" :aria-label="__m('Open Oxen assistant', 'NsOxen')" @click="iconClick" @pointerdown="iconPointerDown" @pointermove="iconPointerMove" @pointerup="iconPointerUp" @pointercancel="iconPointerCancel">
            <img :src="[ 'dark', 'phosphor' ].includes( theme.value ) ? '/modules/nsoxen/img/oxen-no-container-white.svg' : '/modules/nsoxen/img/oxen-no-container-black.svg'" draggable="false" alt="" class="ox:absolute ox:inset-0 ox:m-auto ox:h-8 ox:w-8" aria-hidden="true">
        </button>
        <section v-show="open" data-testid="oxen-popup" role="dialog" aria-modal="true" :aria-label="__m('Oxen assistant', 'NsOxen')" class="ns-oxen-launcher-morph ns-oxen-motion ox:pointer-events-auto ox:absolute ox:flex ox:flex-col ox:overflow-hidden ox:rounded-[30px] ox:border ox:border-box-edge ox:bg-box-background ox:text-fontcolor ox:shadow-2xl ox:max-sm:rounded-none" :style="popupStyle">
                <header class="ox:flex ox:items-center ox:gap-2 ox:border-b ox:border-box-edge ox:p-4">
                    <div class="ox:min-w-0 ox:flex-1">
                        <h2 class="ox:font-semibold">{{ __m('Oxen', 'NsOxen') }}</h2>
                        <p class="ox:text-xs ox:text-fontcolor-soft">{{ configured ? __m('Ready', 'NsOxen') : __m('Setup required', 'NsOxen') }}</p>
                    </div>
                    <button type="button" class="ox:rounded-full ox:w-8 ox:h-8 ox:hover:bg-box-elevation-hover" :aria-label="__m('Conversation history', 'NsOxen')" @click="workspace = workspace === 'history' ? 'chat' : 'history'"><i class="las la-history" aria-hidden="true"></i></button>
                    <button data-testid="oxen-popup-drag-handle" type="button" class="ox:hidden ox:h-8 ox:w-8 ox:touch-none ox:cursor-grab ox:items-center ox:justify-center ox:rounded-full ox:hover:bg-box-elevation-hover ox:active:cursor-grabbing ox:sm:flex" :aria-label="__m('Move Oxen assistant', 'NsOxen')" @pointerdown="popupPointerDown" @pointermove="popupPointerMove" @pointerup="popupPointerUp" @pointercancel="popupPointerCancel"><i class="las la-arrows-alt" aria-hidden="true"></i></button>
                    <button type="button" class="ox:rounded-full ox:w-8 ox:h-8 ox:hover:bg-box-elevation-hover" :aria-label="__m('Close Oxen', 'NsOxen')" @click="toggle"><i class="las la-times" aria-hidden="true"></i></button>
                </header>

                <div v-if="componentIsLoading" class="ox:flex ox:min-h-0 ox:flex-1 ox:items-center ox:justify-center" role="status" :aria-label="__m('Loading Oxen…', 'NsOxen')">
                    <object
                        width="200"
                        height="200"
                        type="image/svg+xml"
                        :data="[ 'dark', 'phosphor' ].includes(theme.value) ? '/modules/nsoxen/img/oxen-no-container-white-animated.svg' : '/modules/nsoxen/img/oxen-no-container-black-animated.svg'"
                        class="ox:h-[100px] ox:w-[100px] ox:max-h-full ox:max-w-full"
                        aria-hidden="true">
                    </object>
                </div>

                <template v-else-if="workspace === 'history'">
                    <main class="ns-scrollbar ox:flex ox:min-h-0 ox:flex-1 ox:flex-col ox:gap-2 ox:overflow-y-auto ox:p-4">
                        <div class="ox:flex ox:items-center ox:justify-between"><strong>{{ __m('Conversations', 'NsOxen') }}</strong><button type="button" class="ox:text-info-tertiary ox:hover:underline" @click="newConversation">{{ __m('Start a new discussion', 'NsOxen') }}</button></div>
                        <button v-for="conversation in conversations" :key="conversation.public_id" type="button" class="ox:flex ox:items-center ox:justify-between ox:gap-3 ox:rounded-lg ox:border ox:border-box-edge ox:p-3 ox:text-left ox:hover:bg-box-elevation-hover" @click="loadConversation(conversation.public_id)"><span class="ox:min-w-0 ox:flex-1 ox:truncate">{{ conversation.title }}</span><time class="ox:shrink-0 ox:text-xs ox:text-fontcolor-soft" :datetime="conversation.last_activity_at">{{ relativeActivity(conversation.last_activity_at) }}</time></button>
                    </main>
                </template>

                <template v-else>
                    <main ref="messageList" class="ns-scrollbar ox:flex ox:min-h-0 ox:flex-1 ox:flex-col ox:gap-3 ox:overflow-y-auto ox:p-4 ox:pb-[180px]">
                        <div v-if="!configured" class="ox:rounded-lg ox:border ox:border-box-edge ox:bg-box-elevation-background/90 ox:p-4">
                            <p>{{ __m('An administrator must configure and test the OpenAI connection.', 'NsOxen') }}</p>
                            <a v-if="canManage" href="/dashboard/settings/ns-oxen-settings" class="ox:text-info-primary ox:hover:underline">{{ __m('Open settings', 'NsOxen') }}</a>
                        </div>
                        <template v-else>
                            <template v-for="(message, index) in messages" :key="index">
                                <article :class="message.role === 'user' ? 'ox:bg-box-elevation-background ox:text-fontcolor ox:rounded-lg ox:p-3' : ''">
                                    <aside v-if="message.type === 'info'" class="ns-oxen-info-message ox:text-sm ox:text-fontcolor-soft" role="status">
                                        <div class="ox:min-w-0">
                                            <p class="ox:mt-0.5 ox:whitespace-pre-wrap">{{ message.content }}</p>
                                        </div>
                                    </aside>
                                    <strong v-if="message.type !== 'info' && message.role === 'user'" class="ox:text-xs">{{ __m('You', 'NsOxen') }}</strong>
                                    <p v-if="message.role === 'user'" class="ox:whitespace-pre-wrap">{{ message.content }}</p>
                                    <ul v-if="message.role === 'user' && message.metadata?.attachments?.length" class="ox:mt-2 ox:flex ox:flex-col ox:gap-1" :aria-label="__m('Attachments', 'NsOxen')">
                                        <li v-for="attachment in message.metadata.attachments" :key="attachment.id" class="ox:flex ox:items-center ox:gap-2 ox:rounded-lg ox:border ox:border-box-edge ox:px-2 ox:py-1 ox:text-xs">
                                            <i class="las la-paperclip" aria-hidden="true"></i><span class="ox:min-w-0 ox:truncate">{{ attachment.name }}</span>
                                        </li>
                                    </ul>
                                    <div v-if="message.type !== 'info' && message.role !== 'user' && message.content" class="ns-oxen-markdown" v-html="renderMarkdown(message.content)"></div>
                                    <p v-else-if="thinking && index === messages.length - 1" class="ns-oxen-thinking" role="status" :aria-label="__m('Thinking…', 'NsOxen')"><span v-for="(character, characterIndex) in thinkingCharacters" :key="characterIndex" aria-hidden="true" :style="{ animationDelay: `${characterIndex * 80}ms` }">{{ character }}</span></p>
                                    <details v-if="message.activity?.length" :open="thinking && index === messages.length - 1" class="ox:mt-3 ox:rounded-lg ox:border ox:border-box-edge ox:bg-box-elevation-background/40 ox:p-2">
                                        <summary class="ox:cursor-pointer ox:text-xs ox:font-semibold ox:text-fontcolor-soft">{{ __m('Activity', 'NsOxen') }} · {{ message.activity.length }}</summary>
                                        <ol class="ox:mt-2 ox:flex ox:flex-col ox:gap-2">
                                            <li v-for="activity in message.activity" :key="activity.data.call_id ?? activity.data.tool" class="ox:flex ox:items-start ox:gap-2 ox:text-xs">
                                                <i v-if="activity.event === 'tool.completed'" class="las la-check-circle ox:text-success-tertiary" aria-hidden="true"></i>
                                                <i v-else-if="activity.event === 'tool.failed'" class="las la-exclamation-circle ox:text-error-tertiary" aria-hidden="true"></i>
                                                <i v-else class="las la-circle-notch la-spin ox:text-info-tertiary" aria-hidden="true"></i>
                                                <span><strong class="ox:font-medium ox:text-fontcolor">{{ toolTitle(activity.data.tool) }}</strong><small v-if="activity.data.summary" class="ox:mt-0.5 ox:block ox:text-fontcolor-soft">{{ activity.data.summary }}</small></span>
                                            </li>
                                        </ol>
                                    </details>
                                    <div v-if="message.actions?.some(action => ['pending', 'expired'].includes(action.status))" class="ox:mt-3 ox:flex ox:flex-col ox:gap-2">
                                        <div v-for="action in message.actions" :key="action.id" class="ox:flex ox:items-center ox:justify-between ox:gap-2 ox:rounded-lg ox:border ox:border-box-edge ox:p-2">
                                            <span class="ox:text-sm">{{ action.title }} <small class="ox:text-fontcolor-soft">({{ action.risk }})</small><small v-if="action.summary" class="ox:mt-0.5 ox:block ox:text-fontcolor-soft">{{ action.summary }}</small></span>
                                            <div v-if="action.status === 'pending'" class="ox:flex ox:gap-1"><button type="button" class="ox:rounded ox:px-2 ox:py-1 ox:text-sm ox:text-info-tertiary ox:hover:underline" @click="rejectAction(action)">{{ __m('Reject', 'NsOxen') }}</button><div class="ns-button info"><button type="button" class="ox:rounded ox:px-2 ox:py-1 ox:text-sm" @click="executeAction(action)">{{ __m('Approve', 'NsOxen') }}</button></div></div>
                                            <button v-else-if="action.status === 'expired'" type="button" class="ox:rounded ox:px-2 ox:py-1 ox:text-sm ox:text-info-tertiary ox:hover:underline" @click="retryAction(action)">{{ __m('Retry', 'NsOxen') }}</button><span v-else class="ox:text-xs ox:text-fontcolor-soft">{{ action.status }}</span>
                                        </div>
                                    </div>
                                </article>
                            </template>
                        </template>
                    </main>
                    <footer class="ox:border-t ox:border-box-edge ox:h-0 ox:w-full ox:relative">
                        <div class="ox:-bottom-[0px] ox:absolute ox:w-full">
                            <div class="ox:bg-box-elevation-background ox:m-3 ox:p-3 ox:rounded-[20px]">
                                <div v-if="attachments.length" class="ox:mb-2 ox:text-xs ox:text-fontcolor-soft">{{ attachments.map(file => file.name).join(', ') }}</div>
                                <textarea ref="composer" v-model="draft" :disabled="!configured || streaming" class="ns-scrollbar ox:outline-none ox:min-h-10 ox:max-h-[400px] ox:w-full ox:resize-none ox:overflow-y-hidden ox:rounded-lg ox:p-3 ox:text-fontcolor" :placeholder="__m('Ask about your store…', 'NsOxen')" @input="persistDraft(); resizeComposer()" @keydown="composerKeydown"></textarea>
                                <div class="ox:mt-2 ox:flex ox:items-center ox:justify-between">
                                    <div class="ox:flex ox:items-center ox:gap-1">
                                        <label class="ox:cursor-pointer ox:rounded-full ox:flex ox:items-center ox:justify-center ox:h-8 ox:w-8 ox:hover:bg-box-elevation-hover" :aria-label="__m('Attach files', 'NsOxen')">
                                            <i class="las la-paperclip" aria-hidden="true"></i>
                                            <input ref="attachmentInput" type="file" multiple accept=".pdf,.csv,.tsv,.xls,.xlsx,.txt,.md,.json,.html,.xml,.doc,.docx,.rtf,.odt,.ppt,.pptx" class="ox:hidden" @change="onAttachments">
                                        </label>
                                        <button type="button" class="ox:rounded-full ox:h-8 ox:w-8 ox:hover:bg-box-elevation-hover" :aria-label="__m('Browse tools', 'NsOxen')" @click="openTools">
                                            <i class="las la-toolbox" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <div>
                                        <button v-if="!streaming" type="button" class="ox:rounded-xl ox:px-3 ox:py-1 ox:hover:bg-primary ox:hover:text-white" :disabled="!configured" @click="send"><i class="las la-paper-plane"></i> {{ __m('Send', 'NsOxen') }}</button>
                                        <button v-else type="button" class="ox:rounded-lg ox:px-4 ox:py-2" @click="controller?.abort()"><i class="las la-stop"></i> {{ __m('Stop', 'NsOxen') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </footer>
                </template>
        </section>
    </div>
</template>
