/**
 * API Barrel Exports
 *
 * NOTE: For better tree-shaking and bundle optimization, prefer direct imports:
 * - import { getProducts } from "@/lib/api-products"
 * - import { getCategories } from "@/lib/api-categories"
 *
 * This barrel file is kept for backward compatibility.
 */
export * from "./types/api-types";
export * from "./api-http";
export * from "./api-pages";
export * from "./api-products";
export * from "./api-menu";
export * from "./api-categories";
export * from "./api-addresses";
export * from "./api-orders";
export * from "./api-shop";
export * from "./api-ecommerce-pages";