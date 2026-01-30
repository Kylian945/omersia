// app/auth/me/route.ts
import { NextRequest, NextResponse } from "next/server";
import { headers } from "next/headers";

const BACKEND_URL = process.env.API_INTERNAL_URL!;
const API_KEY = process.env.FRONT_API_KEY!;

export const dynamic = "force-dynamic";
export const revalidate = 0;

export async function GET(req: NextRequest) {
  const token = req.cookies.get("auth_token")?.value;

  const unauth = NextResponse.json(
    { authenticated: false, user: null },
    {
      status: 200,
      headers: { "Cache-Control": "no-store, no-cache, must-revalidate" },
    }
  );

  if (!token) return unauth;

  try {
    const h = await headers();

    const res = await fetch(`${BACKEND_URL}/auth/me`, {
      method: "GET",
      headers: {
        "X-API-KEY": API_KEY,
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
        // optionnel, utile pour logs backend
        "X-Forwarded-For": h.get("x-forwarded-for") || "",
        "User-Agent": h.get("user-agent") || "",
      },
      cache: "no-store",
    });

    if (!res.ok) {
      // Token is invalid/expired, delete the cookie
      const response = NextResponse.json(
        { authenticated: false, user: null },
        {
          status: 200,
          headers: { "Cache-Control": "no-store, no-cache, must-revalidate" },
        }
      );
      response.cookies.set("auth_token", "", { path: "/", maxAge: 0 });
      return response;
    }

    const user = await res.json();

    return NextResponse.json(
      { authenticated: true, user },
      {
        status: 200,
        headers: { "Cache-Control": "no-store, no-cache, must-revalidate" },
      }
    );
  } catch {
    // Request failed (network error, etc.), delete the cookie if it exists
    const response = NextResponse.json(
      { authenticated: false, user: null },
      {
        status: 200,
        headers: { "Cache-Control": "no-store, no-cache, must-revalidate" },
      }
    );
    if (token) {
      response.cookies.set("auth_token", "", { path: "/", maxAge: 0 });
    }
    return response;
  }
}
