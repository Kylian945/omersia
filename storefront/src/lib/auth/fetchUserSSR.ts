import { cookies } from "next/headers";

export async function fetchUserSSR() {
  const cookieStore = await cookies();
  const cookieHeader = cookieStore.toString();

  // En SSR (Docker), utiliser l'URL interne du conteneur Next.js
  // Par défaut localhost:3000 pour le développement local
  const INTERNAL_URL = process.env.NEXT_INTERNAL_URL || "http://localhost:3000";
  const url = `${INTERNAL_URL}/auth/me`;

  const res = await fetch(url, {
    method: "GET",
    headers: {
      Cookie: cookieHeader,
      Accept: "application/json",
      "Cache-Control": "no-store",
    },
    cache: "no-store",
    next: { revalidate: 0 },
  });

  if (!res.ok) return null;

  const data = await res.json();
  return data?.authenticated ? data.user : null;
}
