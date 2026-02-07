import Pusher from "pusher-js";

let pusherClient = null;

function getRealtimeConfig() {
    return window.omersiaRealtimeConfig ?? null;
}

function isRealtimeReady(config) {
    return Boolean(
        config &&
        config.enabled &&
        typeof config.key === "string" &&
        config.key.length > 0
    );
}

export function createRealtimeClient() {
    if (pusherClient) {
        return pusherClient;
    }

    const config = getRealtimeConfig();
    if (!isRealtimeReady(config)) {
        return null;
    }

    pusherClient = new Pusher(config.key, {
        cluster: config.cluster ?? "mt1",
        wsHost: config.wsHost,
        wsPort: Number(config.wsPort),
        wssPort: Number(config.wssPort),
        forceTLS: Boolean(config.forceTLS),
        enabledTransports: ["ws", "wss"],
        disableStats: true,
        authEndpoint: config.authEndpoint ?? "/broadcasting/auth",
        auth: {
            headers: {
                "X-CSRF-TOKEN": config.csrfToken ?? "",
                "X-Requested-With": "XMLHttpRequest",
            },
        },
    });

    return pusherClient;
}

export function subscribeToPrivateEvent(channelName, eventName, handler) {
    const client = createRealtimeClient();
    if (!client) {
        return null;
    }

    const channel = client.subscribe(`private-${channelName}`);
    channel.bind(eventName, handler);

    return () => {
        channel.unbind(eventName, handler);
        client.unsubscribe(`private-${channelName}`);
    };
}
