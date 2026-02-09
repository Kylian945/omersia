// ⚠️ Ce fichier ne doit être importé que côté serveur
// Pour les appels client, utilisez les routes /api/* de Next.js

if (typeof window !== "undefined") {
  throw new Error(
    "api-http.ts ne peut être importé que côté serveur. " +
    "Utilisez fetch('/api/...') depuis les composants client."
  );
}

export const API_BASE =
  process.env.NEXT_PUBLIC_API_URL ||
  "http://localhost:8000/api/v1";

export const API_KEY = process.env.FRONT_API_KEY;

// Utiliser API_INTERNAL_URL côté serveur (SSR dans Docker), NEXT_PUBLIC_API_URL côté client
const getApiBaseUrl = () => {
  // Côté serveur (SSR)
  if (typeof window === "undefined") {
    return process.env.API_INTERNAL_URL || process.env.NEXT_PUBLIC_API_URL || API_BASE;
  }
  // Côté client (browser)
  return process.env.NEXT_PUBLIC_API_URL || API_BASE;
};

const buildUrl = (path: string) => `${getApiBaseUrl()}${path}`;

export function hasApiKey(): boolean {
  return !!API_KEY && API_KEY.trim().length > 0;
}

async function apiFetch(input: string, init: RequestInit = {}) {
  // Si pas de clé API, retourner une réponse d'erreur silencieuse
  if (!hasApiKey()) {
    return new Response(
      JSON.stringify({ error: 'API key not configured' }),
      {
        status: 500,
        headers: { 'Content-Type': 'application/json' }
      }
    );
  }

  const headers = new Headers(init.headers || {});
  if (API_KEY) {
    headers.set("X-API-KEY", API_KEY);
  }
  headers.set("Accept", "application/json");

  return fetch(input, {
    ...init,
    headers,
  });
}

export function withAuthHeaders(authToken?: string): HeadersInit {
  return authToken
    ? {
        Authorization: `Bearer ${authToken}`,
      }
    : {};
}

export type ApiJsonOptions = {
  method?: "GET" | "POST" | "PUT" | "DELETE";
  authToken?: string;
  body?: unknown;
  cache?: RequestCache;
  nextRevalidate?: number;
  extraHeaders?: HeadersInit;
};

export async function apiJson<T>(
  path: string,
  {
    method = "GET",
    authToken,
    body,
    cache = "no-store",
    nextRevalidate,
    extraHeaders,
  }: ApiJsonOptions = {}
): Promise<{ res: Response; data: T | null }> {
  const url = buildUrl(path);

  const headers: HeadersInit = {
    ...withAuthHeaders(authToken),
    ...(body ? { "Content-Type": "application/json" } : {}),
    ...(extraHeaders || {}),
  };

  const res = await apiFetch(url, {
    method,
    cache,
    ...(nextRevalidate ? { next: { revalidate: nextRevalidate } } : {}),
    headers,
    ...(body ? { body: JSON.stringify(body) } : {}),
  });

  if (!res.ok) {
    return { res, data: null };
  }

  try {
    const json = (await res.json()) as T;
    return { res, data: json };
  } catch {
    return { res, data: null };
  }
}
