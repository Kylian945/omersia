import { subscribeToPrivateEvent } from "./realtime-client";

document.addEventListener("DOMContentLoaded", () => {
    const config = window.omersiaGdprRealtimeConfig ?? {};
    const currentRequestId = Number(config.requestId ?? 0);

    const stopRealtime = subscribeToPrivateEvent(
        "admin.gdpr",
        "gdpr.requests.updated",
        (payload) => {
            const payloadRequestId = Number(payload?.request?.id ?? 0);

            if (currentRequestId > 0 && payloadRequestId > 0 && payloadRequestId !== currentRequestId) {
                return;
            }

            window.location.reload();
        }
    );

    window.addEventListener("beforeunload", () => {
        if (typeof stopRealtime === "function") {
            stopRealtime();
        }
    });
});
