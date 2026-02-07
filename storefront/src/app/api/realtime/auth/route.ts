import { cookies } from "next/headers";
import { NextRequest, NextResponse } from "next/server";
import { safeDecodeURIComponent } from "@/lib/utils/error-utils";

const BACKEND_URL =
  process.env.API_INTERNAL_URL?.replace("/api/v1", "") ||
  process.env.NEXT_PUBLIC_API_URL?.replace("/api/v1", "") ||
  "http://localhost:8000";
const API_KEY = process.env.FRONT_API_KEY;

export async function POST(request: NextRequest) {
  const formData = await request.formData();
  const socketId = formData.get("socket_id");
  const channelName = formData.get("channel_name");

  if (typeof socketId !== "string" || typeof channelName !== "string") {
    return NextResponse.json(
      { message: "Invalid broadcast auth payload" },
      { status: 422 }
    );
  }

  const cookieStore = await cookies();
  const authToken = cookieStore.get("auth_token")?.value;
  const sessionCookie = cookieStore.get("omersia_session");

  if (!authToken) {
    return NextResponse.json({ message: "Unauthenticated" }, { status: 401 });
  }

  const payload = new URLSearchParams({
    socket_id: socketId,
    channel_name: channelName,
  });

  const response = await fetch(`${BACKEND_URL}/api/broadcasting/auth`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/x-www-form-urlencoded",
      "X-API-KEY": API_KEY || "",
      Authorization: `Bearer ${safeDecodeURIComponent(authToken)}`,
      ...(sessionCookie && {
        Cookie: `${sessionCookie.name}=${sessionCookie.value}`,
      }),
    },
    body: payload.toString(),
    cache: "no-store",
  });

  const contentType = response.headers.get("content-type");
  const body = await response.text();

  return new NextResponse(body, {
    status: response.status,
    headers: {
      "Content-Type": contentType || "application/json",
      "Cache-Control": "no-store",
    },
  });
}
