import { NextRequest, NextResponse } from "next/server";

const BACKEND_URL = process.env.API_INTERNAL_URL;
const API_KEY = process.env.FRONT_API_KEY;

export async function GET(request: NextRequest) {
  const searchParams = request.nextUrl.searchParams;
  const query = searchParams.get("q") || "";
  const limit = searchParams.get("limit") || "5";
  const locale = searchParams.get("locale") || "fr";

  // Filtres
  const categories = searchParams.get("categories");
  const minPrice = searchParams.get("min_price");
  const maxPrice = searchParams.get("max_price");
  const inStockOnly = searchParams.get("in_stock_only");

  if (!query.trim() || query.length < 2) {
    return NextResponse.json({
      query,
      total: 0,
      products: [],
      facets: {
        categories: [],
        price_range: { min: 0, max: 0 },
      },
    });
  }

  try {
    // Construire l'URL avec les filtres
    let url = `${BACKEND_URL}/search?q=${encodeURIComponent(query)}&limit=${limit}&locale=${locale}`;

    if (categories) {
      url += `&categories=${categories}`;
    }
    if (minPrice) {
      url += `&min_price=${minPrice}`;
    }
    if (maxPrice) {
      url += `&max_price=${maxPrice}`;
    }
    if (inStockOnly) {
      url += `&in_stock_only=${inStockOnly}`;
    }

    const response = await fetch(url, {
      headers: {
        "X-API-KEY": API_KEY || "",
        Accept: "application/json",
      },
      cache: "no-store",
    });

    if (!response.ok) {
      return NextResponse.json(
        { error: "Search failed" },
        { status: response.status }
      );
    }

    const data = await response.json();
    return NextResponse.json(data);
  } catch (error) {
    console.error("Search error:", error);
    return NextResponse.json(
      { error: "Internal server error" },
      { status: 500 }
    );
  }
}
