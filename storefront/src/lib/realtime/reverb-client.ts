import Pusher from "pusher-js";
import { logger } from "@/lib/logger";

type RealtimeEventHandler<TPayload> = (payload: TPayload) => void;
type RealtimeSubscription = () => void;
type RawRealtimeCallback = (payload: unknown) => void;
type EventCallbackSet = Set<RawRealtimeCallback>;
type EventCallbackMap = Map<string, EventCallbackSet>;

declare global {
  interface Window {
    __omersiaRealtimeClient?: Pusher;
    __omersiaConnectionHandlersBound?: boolean;
  }
}

const channelEventCallbacks = new Map<string, EventCallbackMap>();
const channelEventDispatchers = new Map<string, Map<string, RawRealtimeCallback>>();
const channelSubscriberCounts = new Map<string, number>();

function getRealtimeConfig() {
  const key =
    process.env.NEXT_PUBLIC_REVERB_APP_KEY ||
    process.env.NEXT_PUBLIC_PUSHER_APP_KEY;

  if (!key) {
    return null;
  }

  const scheme =
    process.env.NEXT_PUBLIC_REVERB_SCHEME ||
    process.env.NEXT_PUBLIC_PUSHER_SCHEME ||
    (window.location.protocol === "https:" ? "https" : "http");
  const host =
    process.env.NEXT_PUBLIC_REVERB_HOST ||
    process.env.NEXT_PUBLIC_PUSHER_HOST ||
    window.location.hostname;

  const defaultPort = scheme === "https" ? 443 : 80;
  const configuredPort =
    process.env.NEXT_PUBLIC_REVERB_PORT || process.env.NEXT_PUBLIC_PUSHER_PORT;
  const port = configuredPort ? Number(configuredPort) : defaultPort;

  return {
    key,
    host,
    port: Number.isFinite(port) && port > 0 ? port : defaultPort,
    forceTLS: scheme === "https",
    cluster: process.env.NEXT_PUBLIC_PUSHER_APP_CLUSTER || "mt1",
  };
}

function getRealtimeClient(): Pusher | null {
  if (typeof window === "undefined") {
    return null;
  }

  if (window.__omersiaRealtimeClient) {
    return window.__omersiaRealtimeClient;
  }

  const config = getRealtimeConfig();
  if (!config) {
    return null;
  }

  window.__omersiaRealtimeClient = new Pusher(config.key, {
    cluster: config.cluster,
    wsHost: config.host,
    wsPort: config.port,
    wssPort: config.port,
    forceTLS: config.forceTLS,
    enabledTransports: ["ws", "wss"],
    disableStats: true,
    authEndpoint: "/api/realtime/auth",
    auth: {
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    },
  });

  if (!window.__omersiaConnectionHandlersBound) {
    window.__omersiaConnectionHandlersBound = true;

    window.__omersiaRealtimeClient.connection.bind("connected", () => {
      logger.info("Realtime connected");
    });
    window.__omersiaRealtimeClient.connection.bind("error", (error: unknown) => {
      logger.warn("Realtime connection error", error);
    });
    window.__omersiaRealtimeClient.connection.bind("disconnected", () => {
      logger.warn("Realtime disconnected");
    });
  }

  return window.__omersiaRealtimeClient;
}

type SubscribeOptions<TPayload> = {
  channelName: string;
  eventName: string;
  onEvent: RealtimeEventHandler<TPayload>;
};

export async function subscribeToPrivateRealtimeEvent<TPayload>({
  channelName,
  eventName,
  onEvent,
}: SubscribeOptions<TPayload>): Promise<RealtimeSubscription | null> {
  const client = getRealtimeClient();
  if (!client) {
    return null;
  }

  const privateChannel = `private-${channelName}`;
  const channel = client.subscribe(privateChannel);
  const callback = (payload: unknown) => onEvent(payload as TPayload);
  const currentCount = channelSubscriberCounts.get(privateChannel) ?? 0;
  channelSubscriberCounts.set(privateChannel, currentCount + 1);

  let eventMap = channelEventCallbacks.get(privateChannel);
  if (!eventMap) {
    eventMap = new Map();
    channelEventCallbacks.set(privateChannel, eventMap);
  }

  let callbacks = eventMap.get(eventName);
  if (!callbacks) {
    callbacks = new Set();
    eventMap.set(eventName, callbacks);
  }
  callbacks.add(callback);

  let dispatchers = channelEventDispatchers.get(privateChannel);
  if (!dispatchers) {
    dispatchers = new Map();
    channelEventDispatchers.set(privateChannel, dispatchers);
  }

  if (!dispatchers.has(eventName)) {
    const dispatcher: RawRealtimeCallback = (payload: unknown) => {
      const channelCallbacks = channelEventCallbacks
        .get(privateChannel)
        ?.get(eventName);

      if (!channelCallbacks || channelCallbacks.size === 0) {
        return;
      }

      for (const fn of channelCallbacks) {
        fn(payload);
      }
    };

    dispatchers.set(eventName, dispatcher);
    channel.bind(eventName, dispatcher);
  }

  return () => {
    const eventCallbacks = channelEventCallbacks
      .get(privateChannel)
      ?.get(eventName);
    eventCallbacks?.delete(callback);

    if (eventCallbacks && eventCallbacks.size === 0) {
      const dispatcher = channelEventDispatchers
        .get(privateChannel)
        ?.get(eventName);
      if (dispatcher) {
        channel.unbind(eventName, dispatcher);
      }

      channelEventCallbacks.get(privateChannel)?.delete(eventName);
      channelEventDispatchers.get(privateChannel)?.delete(eventName);
    }

    const nextCount = Math.max(
      0,
      (channelSubscriberCounts.get(privateChannel) ?? 1) - 1
    );
    channelSubscriberCounts.set(privateChannel, nextCount);

    const hasEventBindings =
      (channelEventCallbacks.get(privateChannel)?.size ?? 0) > 0;

    if (nextCount === 0 && !hasEventBindings) {
      channelSubscriberCounts.delete(privateChannel);
      channelEventCallbacks.delete(privateChannel);
      channelEventDispatchers.delete(privateChannel);
      client.unsubscribe(privateChannel);
    }
  };
}
