import { subscribeToPrivateEvent } from "./realtime-client";

const DEFAULT_SOUND_URL = "/admin/notifications/payment-success-audio";
const STORAGE_KEY = "omersia_paid_order_notifications";
const MAX_TRACKED_ORDERS = 150;
let hasUnlockedAudio = false;
const pendingOrderIds = new Set();

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
            .map((value) => Number(value))
            .filter((value) => Number.isFinite(value) && value > 0);
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

function hasAlreadyNotified(orderId) {
    return readNotifiedOrders().includes(orderId);
}

function markAsNotified(orderId) {
    const current = readNotifiedOrders().filter((id) => id !== orderId);
    current.push(orderId);

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

function getNotificationAudio() {
    const soundUrl = resolveSoundUrl();

    if (notificationAudio) {
        if (notificationAudio.src !== soundUrl) {
            notificationAudio.src = soundUrl;
            notificationAudio.load();
        }
        return notificationAudio;
    }

    notificationAudio = new Audio(soundUrl);
    notificationAudio.preload = "auto";

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
        flushPendingNotifications();
    } catch {
        // Keep silent: some browsers still block until another interaction.
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

async function tryPlayForOrder(orderId) {
    const isPlayable = hasUnlockedAudio || await unlockAudioAfterUserInteraction();
    if (!isPlayable) {
        pendingOrderIds.add(orderId);
        return;
    }

    const played = await playSound();
    if (played) {
        markAsNotified(orderId);
        pendingOrderIds.delete(orderId);
        return;
    }

    pendingOrderIds.add(orderId);
}

function flushPendingNotifications() {
    if (!hasUnlockedAudio || pendingOrderIds.size === 0) {
        return;
    }

    const ids = Array.from(pendingOrderIds);
    ids.forEach((orderId) => {
        void tryPlayForOrder(orderId);
    });
}

function initOrderPaidNotification() {
    window.addEventListener("pointerdown", unlockAudioAfterUserInteraction, {
        once: true,
        passive: true,
    });

    window.addEventListener("keydown", unlockAudioAfterUserInteraction, {
        once: true,
    });

    window.addEventListener("touchstart", unlockAudioAfterUserInteraction, {
        once: true,
        passive: true,
    });

    // Eager preload to reduce delay on first notification.
    getNotificationAudio().load();

    const stopRealtime = subscribeToPrivateEvent(
        "admin.orders",
        "orders.updated",
        (payload) => {
            const order = payload?.order;
            const orderId = Number(order?.id ?? 0);

            if (!Number.isFinite(orderId) || orderId <= 0) {
                return;
            }

            if (!isPaidAndEligible(order)) {
                return;
            }

            if (hasAlreadyNotified(orderId)) {
                return;
            }

            void tryPlayForOrder(orderId);
        }
    );

    window.addEventListener("beforeunload", () => {
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
