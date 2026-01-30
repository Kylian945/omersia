import { NextRequest, NextResponse } from "next/server";
import { cookies } from "next/headers";

const BACKEND_URL = process.env.API_INTERNAL_URL?.replace('/api/v1', '') || process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";
const API_KEY = process.env.FRONT_API_KEY;

export async function GET(request: NextRequest) {
  try {
    const cookieStore = await cookies();
    const sessionCookie = cookieStore.get("omersia_session");
    const authToken = cookieStore.get("auth_token");

    if (!authToken) {
      return NextResponse.json(
        { error: "Unauthenticated" },
        { status: 401 }
      );
    }

    const res = await fetch(`${BACKEND_URL}/api/v1/gdpr/data-requests`, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-API-KEY": API_KEY || "",
        Authorization: `Bearer ${decodeURIComponent(authToken.value)}`,
        ...(sessionCookie && {
          Cookie: `${sessionCookie.name}=${sessionCookie.value}`,
        }),
      },
    });

    if (!res.ok) {
      const error = await res.json();
      return NextResponse.json(error, { status: res.status });
    }

    const data = await res.json();
    return NextResponse.json(data);
  } catch (error) {
    console.error("Error fetching data requests:", error);
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

    if (!authToken) {
      return NextResponse.json(
        { error: "Unauthenticated" },
        { status: 401 }
      );
    }

    const res = await fetch(`${BACKEND_URL}/api/v1/gdpr/data-requests`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-API-KEY": API_KEY || "",
        Authorization: `Bearer ${decodeURIComponent(authToken.value)}`,
        ...(sessionCookie && {
          Cookie: `${sessionCookie.name}=${sessionCookie.value}`,
        }),
      },
      body: JSON.stringify({
        type: body.type,
        reason: body.reason,
      }),
    });

    if (!res.ok) {
      const error = await res.json();
      return NextResponse.json(error, { status: res.status });
    }

    const data = await res.json();
    return NextResponse.json(data, { status: 201 });
  } catch (error) {
    console.error("Error creating data request:", error);
    return NextResponse.json(
      { error: "Internal server error" },
      { status: 500 }
    );
  }
}
