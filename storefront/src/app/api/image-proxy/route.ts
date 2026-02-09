import { NextRequest, NextResponse } from "next/server";

/**
 * Image proxy for Docker environment
 *
 * This route proxies image requests to the backend, handling Docker network routing.
 * It allows Next.js Image optimization to work by providing a consistent URL that
 * works both server-side (in Docker) and client-side (browser).
 *
 * Usage: /api/image-proxy?path=/storage/products/1/image.png
 */

// Backend URL for fetching images (internal Docker network)
const BACKEND_INTERNAL_URL =
  process.env.BACKEND_URL ||
  process.env.API_INTERNAL_URL?.replace("/api/v1", "") ||
  "http://localhost:8000";

export async function GET(request: NextRequest) {
  const path = request.nextUrl.searchParams.get("path");

  if (!path) {
    return new NextResponse("Missing path parameter", { status: 400 });
  }

  try {
    // Build the internal URL to fetch from
    const cleanPath = path.startsWith("/") ? path : `/${path}`;
    const imageUrl = `${BACKEND_INTERNAL_URL}${cleanPath}`;

    // Fetch the image from the backend
    const response = await fetch(imageUrl, {
      headers: {
        // Forward accept header for content negotiation
        Accept: request.headers.get("Accept") || "image/*",
      },
    });

    if (!response.ok) {
      return new NextResponse(`Failed to fetch image: ${response.status}`, {
        status: response.status,
      });
    }

    // Get the image data
    const imageData = await response.arrayBuffer();

    // Return the image with appropriate headers
    return new NextResponse(imageData, {
      status: 200,
      headers: {
        "Content-Type": response.headers.get("Content-Type") || "image/png",
        "Cache-Control": "public, max-age=31536000, immutable",
        "Content-Length": imageData.byteLength.toString(),
      },
    });
  } catch (error) {
    console.error("Image proxy error:", error);
    return new NextResponse("Failed to fetch image", { status: 500 });
  }
}
