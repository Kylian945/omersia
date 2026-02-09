import { cookies } from "next/headers";
import { NextRequest, NextResponse } from "next/server";
import { API_KEY } from "@/lib/api-http";
import { logger } from "@/lib/logger";

type Params = {
  number: string;
};

// Utiliser API_INTERNAL_URL côté serveur (SSR dans Docker), NEXT_PUBLIC_API_URL côté client
const getApiBaseUrl = () => {
  return process.env.API_INTERNAL_URL || process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api/v1";
};

export async function GET(
  _request: NextRequest,
  { params }: { params: Promise<Params> }
) {
  const { number } = await params;
  const authToken = (await cookies()).get("auth_token")?.value;

  if (!authToken) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  const apiUrl = getApiBaseUrl();

  try {
    const response = await fetch(`${apiUrl}/orders/${number}/invoice`, {
      method: "GET",
      headers: {
        Authorization: `Bearer ${authToken}`,
        "X-API-Key": API_KEY || "",
      },
    });

    if (!response.ok) {
      logger.error("Backend invoice download failed:", response.status);
      return NextResponse.json(
        { error: "Failed to download invoice" },
        { status: response.status }
      );
    }

    const blob = await response.blob();

    return new NextResponse(blob, {
      headers: {
        "Content-Type": "application/pdf",
        "Content-Disposition": `attachment; filename="facture-${number}.pdf"`,
      },
    });
  } catch (error) {
    logger.error("Error downloading invoice:", error);
    return NextResponse.json(
      { error: "Internal server error" },
      { status: 500 }
    );
  }
}
