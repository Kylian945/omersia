import { NextRequest, NextResponse } from "next/server";
import { logger } from "@/lib/logger";

const BACKEND_URL = process.env.API_INTERNAL_URL;
const API_KEY = process.env.FRONT_API_KEY;

type RouteContext = {
  params: {
    slug: string;
  };
};

export async function GET(request: NextRequest, { params }: RouteContext) {
  if (!BACKEND_URL) {
    return NextResponse.json(
      { error: "Backend URL not configured" },
      { status: 500 }
    );
  }

  const slug = params.slug;
  if (!slug) {
    return NextResponse.json(
      { error: "Missing product slug" },
      { status: 400 }
    );
  }

  const locale = request.nextUrl.searchParams.get("locale") || "fr";

  try {
    const response = await fetch(
      `${BACKEND_URL}/products/${encodeURIComponent(slug)}?locale=${encodeURIComponent(locale)}`,
      {
        headers: {
          "X-API-KEY": API_KEY || "",
          Accept: "application/json",
        },
        cache: "no-store",
      }
    );

    if (!response.ok) {
      return NextResponse.json(
        { error: "Failed to fetch product details" },
        { status: response.status }
      );
    }

    const data = await response.json();
    return NextResponse.json(data);
  } catch (error) {
    logger.error("Product details proxy error:", error);
    return NextResponse.json(
      { error: "Internal server error" },
      { status: 500 }
    );
  }
}

