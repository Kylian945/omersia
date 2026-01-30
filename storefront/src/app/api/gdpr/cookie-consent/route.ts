import { NextRequest, NextResponse } from "next/server";
import { cookies } from "next/headers";

const BACKEND_URL = process.env.API_INTERNAL_URL?.replace('/api/v1', '') || process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";
const API_KEY = process.env.FRONT_API_KEY;

export async function GET(request: NextRequest) {
  try {
    const cookieStore = await cookies();
    const sessionCookie = cookieStore.get("omersia_session");
    const authToken = cookieStore.get("auth_token");

    const res = await fetch(`${BACKEND_URL}/api/v1/gdpr/cookie-consent`, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-API-KEY": API_KEY || "",
        ...(authToken && {
          Authorization: `Bearer ${decodeURIComponent(authToken.value)}`,
        }),
        ...(sessionCookie && {
          Cookie: `${sessionCookie.name}=${sessionCookie.value}`,
        }),
      },
    });

    if (!res.ok) {
      return NextResponse.json(
        { error: "Failed to fetch cookie consent" },
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
    console.error("Error fetching cookie consent:", error);
    return NextResponse.json(
      { error: "Internal server error" },
      { status: 500 }
    );
  }
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const cookieStore = await cookies();
    const sessionCookie = cookieStore.get("omersia_session");
    const authToken = cookieStore.get("auth_token");

    const res = await fetch(`${BACKEND_URL}/api/v1/gdpr/cookie-consent`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-API-KEY": API_KEY || "",
        ...(authToken && {
          Authorization: `Bearer ${decodeURIComponent(authToken.value)}`,
        }),
        ...(sessionCookie && {
          Cookie: `${sessionCookie.name}=${sessionCookie.value}`,
        }),
      },
      body: JSON.stringify({
        functional: body.functional,
        analytics: body.analytics,
        marketing: body.marketing,
      }),
    });

    if (!res.ok) {
      const error = await res.json();
      return NextResponse.json(error, { status: res.status });
    }

    const data = await res.json();

    // Créer la réponse et propager les cookies de Laravel au client
    const response = NextResponse.json(data, { status: 201 });

    // Récupérer et transmettre le cookie de session Laravel
    const setCookieHeaders = res.headers.getSetCookie();
    if (setCookieHeaders) {
      setCookieHeaders.forEach((cookie) => {
        response.headers.append('Set-Cookie', cookie);
      });
    }

    return response;
  } catch (error) {
    console.error("Error saving cookie consent:", error);
    return NextResponse.json(
      { error: "Internal server error" },
      { status: 500 }
    );
  }
}
