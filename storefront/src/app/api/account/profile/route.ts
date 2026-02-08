import { NextRequest, NextResponse } from "next/server";
import { cookies } from "next/headers";
import { apiJson } from "@/lib/api-http";

type ProfilePayload = {
  firstname: string;
  lastname: string;
  email: string;
  phone?: string | null;
};

export async function GET() {
  const authToken = (await cookies()).get("auth_token")?.value;

  if (!authToken) {
    return NextResponse.json(
      { error: "Unauthenticated" },
      { status: 401 }
    );
  }

  const { res, data } = await apiJson<ProfilePayload>("/account/profile", {
    method: "GET",
    extraHeaders: {
      Authorization: `Bearer ${authToken}`,
      Accept: "application/json",
    },
  });

  if (!res.ok || !data) {
    const text = await res.text().catch(() => null);
    return NextResponse.json(
      {
        error: "Profile fetch failed",
        status: res.status,
        backend: text || null,
      },
      { status: res.status }
    );
  }

  return NextResponse.json(data);
}

export async function PUT(req: NextRequest) {
  const authToken = (await cookies()).get("auth_token")?.value;

  if (!authToken) {
    return NextResponse.json(
      { error: "Unauthenticated" },
      { status: 401 }
    );
  }

  const body = await req.json().catch(() => null);
  if (!body) {
    return NextResponse.json(
      { error: "Invalid JSON body" },
      { status: 400 }
    );
  }

  const { res, data } = await apiJson<ProfilePayload>("/account/profile", {
    method: "PUT",
    body,
    extraHeaders: {
      Authorization: `Bearer ${authToken}`,
      "Content-Type": "application/json",
      Accept: "application/json",
    },
  });

  if (!res.ok || !data) {
    const text = await res.text().catch(() => null);
    return NextResponse.json(
      {
        error: "Profile update failed",
        status: res.status,
        backend: text || null,
      },
      { status: res.status }
    );
  }

  return NextResponse.json(data);
}
