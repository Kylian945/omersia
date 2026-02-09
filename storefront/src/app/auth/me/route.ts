// app/auth/me/route.ts
import { NextRequest, NextResponse } from "next/server";
import { headers } from "next/headers";
import { safeDecodeURIComponent } from "@/lib/utils/error-utils";

const BACKEND_URL = process.env.API_INTERNAL_URL!;
const API_KEY = process.env.FRONT_API_KEY!;

export const dynamic = "force-dynamic";
export const revalidate = 0;

type AuthMeResponse = {
  authenticated: boolean;
  user: unknown | null;
  unavailable?: boolean;
};

function authResponse(
  payload: AuthMeResponse,
  status = 200
): NextResponse<AuthMeResponse> {
  return NextResponse.json(payload, {
    status,
    headers: { "Cache-Control": "no-store, no-cache, must-revalidate" },
  });
}

export async function GET(req: NextRequest) {
  const token = req.cookies.get("auth_token")?.value;

  const unauth = authResponse({ authenticated: false, user: null });

  if (!token) return unauth;

  try {
    const h = await headers();
    const decodedToken = safeDecodeURIComponent(token);

    const res = await fetch(`${BACKEND_URL}/auth/me`, {
      method: "GET",
      headers: {
        "X-API-KEY": API_KEY,
        Accept: "application/json",
        Authorization: `Bearer ${decodedToken}`,
        // optionnel, utile pour logs backend
        "X-Forwarded-For": h.get("x-forwarded-for") || "",
        "User-Agent": h.get("user-agent") || "",
      },
      cache: "no-store",
    });

    if (res.status === 401 || res.status === 403) {
      const response = authResponse({ authenticated: false, user: null });
      response.cookies.set("auth_token", "", { path: "/", maxAge: 0 });
      return response;
    }

    if (!res.ok) {
      return authResponse(
        { authenticated: false, user: null, unavailable: true },
        503
      );
    }

    const user = await res.json();

    return authResponse(
      { authenticated: true, user },
    );
  } catch {
    return authResponse(
      { authenticated: false, user: null, unavailable: true },
      503
    );
  }
}
