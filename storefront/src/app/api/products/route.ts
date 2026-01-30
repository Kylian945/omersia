import { NextRequest, NextResponse } from "next/server";
import { getProducts } from "@/lib/api-products";

export async function GET(request: NextRequest) {
  const searchParams = request.nextUrl.searchParams;
  const page = parseInt(searchParams.get("page") || "1", 10);
  const locale = searchParams.get("locale") || "fr";

  const products = await getProducts(locale, page);

  if (!products) {
    return NextResponse.json(
      { error: "Failed to fetch products" },
      { status: 500 }
    );
  }

  return NextResponse.json(products);
}
