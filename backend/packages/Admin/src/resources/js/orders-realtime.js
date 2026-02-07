import { subscribeToPrivateEvent } from "./realtime-client";

document.addEventListener("DOMContentLoaded", () => {
    const config = window.omersiaOrderRealtimeConfig ?? {};
    const currentOrderId = Number(config.orderId ?? 0);

    const stopRealtime = subscribeToPrivateEvent(
        "admin.orders",
        "orders.updated",
        (payload) => {
            const payloadOrderId = Number(payload?.order?.id ?? 0);

            if (currentOrderId > 0 && payloadOrderId > 0 && payloadOrderId !== currentOrderId) {
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
