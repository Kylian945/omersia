import { NextRequest, NextResponse } from "next/server";
import { cookies } from "next/headers";
import { logger } from "@/lib/logger";

const BACKEND_URL = process.env.API_INTERNAL_URL?.replace('/api/v1', '') || process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";
const API_KEY = process.env.FRONT_API_KEY;

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ type: string }> }
) {
  try {
    const { type } = await params;
    const cookieStore = await cookies();
    const sessionCookie = cookieStore.get("omersia_session");
    const authToken = cookieStore.get("auth_token");

    const res = await fetch(
      `${BACKEND_URL}/api/v1/gdpr/cookie-consent/check/${type}`,
      {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          "X-API-KEY": API_KEY || "",
          ...(authToken && {
            Authorization: `Bearer ${authToken.value}`,
          }),
          ...(sessionCookie && {
            Cookie: `${sessionCookie.name}=${sessionCookie.value}`,
          }),
        },
      }
    );

    if (!res.ok) {
      return NextResponse.json(
        { error: "Failed to check cookie consent" },
        { status: res.status }
      );
    }

    const data = await res.json();

    // Créer la réponse et propager les cookies de Laravel au client
    const response = NextResponse.json(data);

    // Récupérer et transmettre le cookie de session Laravel
    const setCookieHeaders = res.headers.getSetCookie();
    if (setCookieHeaders) {
      setCookieHeaders.forEach((cookie) => {
        response.headers.append('Set-Cookie', cookie);
      });
    }

    return response;
  } catch (error) {
    logger.error("Error checking cookie consent:", error);
    return NextResponse.json(
      { error: "Internal server error" },
      { status: 500 }
    );
  }
}
