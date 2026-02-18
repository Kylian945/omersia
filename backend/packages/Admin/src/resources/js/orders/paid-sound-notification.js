import { subscribeToPrivateEvent } from "../core/realtime-client";

const DEFAULT_SOUND_URL = "/admin/notifications/payment-success-audio";
const FALLBACK_SOUND_URL = "/notifications/notif_payment_success.mp3";
const STORAGE_KEY = "omersia_paid_order_notifications";
const MAX_TRACKED_ORDERS = 150;
let hasUnlockedAudio = false;
const pendingOrderKeys = new Set();
let removeUnlockListeners = null;
let hasSwitchedToFallbackSource = false;

function readNotifiedOrders() {
    try {
        const raw = window.sessionStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return [];
        }

        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) {
            return [];
        }

        return parsed
            .map((value) => String(value))
            .filter((value) => value.length > 0);
    } catch {
        return [];
    }
}

function writeNotifiedOrders(orderIds) {
    try {
        window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(orderIds));
    } catch {
        // Ignore storage failures (private mode / quota / disabled storage).
    }
}

function hasAlreadyNotified(orderKey) {
    return readNotifiedOrders().includes(orderKey);
}

function markAsNotified(orderKey) {
    const current = readNotifiedOrders().filter((id) => id !== orderKey);
    current.push(orderKey);

    // Keep storage bounded.
    const bounded = current.slice(-MAX_TRACKED_ORDERS);
    writeNotifiedOrders(bounded);
}

let notificationAudio = null;

function resolveSoundUrl() {
    const configuredUrl =
        window.omersiaOrderSoundConfig?.paymentSuccessUrl || DEFAULT_SOUND_URL;

    try {
        return new URL(configuredUrl, window.location.origin).toString();
    } catch {
        return DEFAULT_SOUND_URL;
    }
}

function resolveFallbackSoundUrl() {
    try {
        return new URL(FALLBACK_SOUND_URL, window.location.origin).toString();
    } catch {
        return FALLBACK_SOUND_URL;
    }
}

function getNotificationAudio() {
    const soundUrl = resolveSoundUrl();

    if (notificationAudio) {
        if (notificationAudio.src !== soundUrl) {
            hasSwitchedToFallbackSource = false;
            notificationAudio.src = soundUrl;
            notificationAudio.load();
        }
        return notificationAudio;
    }

    notificationAudio = new Audio();
    notificationAudio.src = soundUrl;
    notificationAudio.preload = "auto";
    notificationAudio.addEventListener("error", () => {
        if (hasSwitchedToFallbackSource) {
            return;
        }

        hasSwitchedToFallbackSource = true;
        notificationAudio.src = resolveFallbackSoundUrl();
        notificationAudio.load();
    });

    return notificationAudio;
}

async function playSound() {
    try {
        const audio = getNotificationAudio();
        audio.currentTime = 0;
        await audio.play();
        return true;
    } catch (error) {
        // Browser may block autoplay until first user interaction.
        console.warn("Unable to play payment notification sound", error);
        return false;
    }
}

async function unlockAudioAfterUserInteraction() {
    if (hasUnlockedAudio) {
        detachUnlockListeners();
        return true;
    }

    try {
        const audio = getNotificationAudio();
        audio.muted = true;
        await audio.play();
        audio.pause();
        audio.currentTime = 0;
        audio.muted = false;
        hasUnlockedAudio = true;
        detachUnlockListeners();
        flushPendingNotifications();
    } catch {
        // Keep silent and keep listeners attached to retry on next interaction.
    }

    return hasUnlockedAudio;
}

function isPaidAndEligible(order) {
    if (order?.payment_status !== "paid") {
        return false;
    }

    // Ignore drafts/cancelled to avoid false positives.
    return order?.status !== "draft" && order?.status !== "cancelled";
}

function buildOrderNotificationKey(order) {
    const orderId = Number(order?.id ?? 0);
    if (!Number.isFinite(orderId) || orderId <= 0) {
        return null;
    }

    const placedAt = typeof order?.placed_at === "string" && order.placed_at.length > 0
        ? order.placed_at
        : "na";

    return `${orderId}:${placedAt}`;
}

async function tryPlayForOrder(orderKey) {
    const isPlayable = hasUnlockedAudio || await unlockAudioAfterUserInteraction();
    if (!isPlayable) {
        pendingOrderKeys.add(orderKey);
        return;
    }

    const played = await playSound();
    if (played) {
        markAsNotified(orderKey);
        pendingOrderKeys.delete(orderKey);
        return;
    }

    pendingOrderKeys.add(orderKey);
}

function flushPendingNotifications() {
    if (!hasUnlockedAudio || pendingOrderKeys.size === 0) {
        return;
    }

    const keys = Array.from(pendingOrderKeys);
    keys.forEach((orderKey) => {
        void tryPlayForOrder(orderKey);
    });
}

function detachUnlockListeners() {
    if (typeof removeUnlockListeners === "function") {
        removeUnlockListeners();
        removeUnlockListeners = null;
    }
}

function attachUnlockListeners() {
    if (removeUnlockListeners) {
        return;
    }

    const handler = () => {
        void unlockAudioAfterUserInteraction();
    };

    window.addEventListener("pointerdown", handler, {
        passive: true,
    });

    window.addEventListener("keydown", handler);

    window.addEventListener("touchstart", handler, {
        passive: true,
    });

    removeUnlockListeners = () => {
        window.removeEventListener("pointerdown", handler);
        window.removeEventListener("keydown", handler);
        window.removeEventListener("touchstart", handler);
    };
}

function initOrderPaidNotification() {
    attachUnlockListeners();

    // Eager preload to reduce delay on first notification.
    getNotificationAudio().load();

    const stopRealtime = subscribeToPrivateEvent(
        "admin.orders",
        "orders.updated",
        (payload) => {
            const order = payload?.order;
            const orderKey = buildOrderNotificationKey(order);
            if (!orderKey) {
                return;
            }

            if (!isPaidAndEligible(order)) {
                return;
            }

            if (hasAlreadyNotified(orderKey)) {
                return;
            }

            void tryPlayForOrder(orderKey);
        }
    );

    window.addEventListener("beforeunload", () => {
        detachUnlockListeners();
        if (typeof stopRealtime === "function") {
            stopRealtime();
        }
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initOrderPaidNotification, {
        once: true,
    });
} else {
    initOrderPaidNotification();
}
