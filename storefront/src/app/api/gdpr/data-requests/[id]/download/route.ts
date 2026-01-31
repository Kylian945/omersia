import { NextRequest, NextResponse } from "next/server";
import { cookies } from "next/headers";
import { safeDecodeURIComponent } from "@/lib/utils/error-utils";
import { logger } from "@/lib/logger";

const BACKEND_URL = process.env.API_INTERNAL_URL?.replace('/api/v1', '') || process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";
const API_KEY = process.env.FRONT_API_KEY;

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    const { id } = await params;
    const cookieStore = await cookies();
    const sessionCookie = cookieStore.get("omersia_session");
    const authToken = cookieStore.get("auth_token");

    if (!authToken) {
      return NextResponse.json(
        { error: "Unauthenticated" },
        { status: 401 }
      );
    }

    const res = await fetch(
      `${BACKEND_URL}/api/v1/gdpr/data-requests/${id}/download`,
      {
        method: "GET",
        headers: {
          Accept: "application/json",
          "X-API-KEY": API_KEY || "",
          Authorization: `Bearer ${safeDecodeURIComponent(authToken.value)}`,
          ...(sessionCookie && {
            Cookie: `${sessionCookie.name}=${sessionCookie.value}`,
          }),
        },
      }
    );

    if (!res.ok) {
      const error = await res.json();
      return NextResponse.json(error, { status: res.status });
    }

    // Récupérer le contenu du fichier
    const content = await res.text();

    // Retourner le fichier avec les bons headers
    return new NextResponse(content, {
      status: 200,
      headers: {
        "Content-Type": "application/json",
        "Content-Disposition": "attachment; filename=my_data_export.json",
      },
    });
  } catch (error) {
    logger.error("Error downloading data export:", error);
    return NextResponse.json(
      { error: "Internal server error" },
      { status: 500 }
    );
  }
}
