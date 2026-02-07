import { subscribeToPrivateEvent } from "./realtime-client";

function updateProductStockNode(payload) {
    const product = payload?.product;
    const productId = Number(product?.id ?? 0);
    if (!Number.isFinite(productId) || productId <= 0) {
        return;
    }

    const valueEl = document.getElementById(`product-stock-value-${productId}`);
    const noteEl = document.getElementById(`product-stock-note-${productId}`);
    if (!valueEl) {
        return;
    }

    const isVariant = String(product?.type ?? "") === "variant" || Number(product?.variants_count ?? 0) > 0;
    const manageStock = isVariant ? true : Boolean(product?.manage_stock);
    const stockQty = Number(product?.stock_qty ?? 0);

    valueEl.classList.remove("text-red-600", "text-gray-900");

    if (manageStock) {
        valueEl.textContent = String(Number.isFinite(stockQty) ? stockQty : 0);
        valueEl.classList.add(stockQty <= 0 ? "text-red-600" : "text-gray-900");
    } else {
        valueEl.textContent = "—";
        valueEl.classList.add("text-gray-900");
    }

    if (noteEl) {
        if (isVariant) {
            const variantsCount = Number(product?.variants_count ?? 0);
            noteEl.textContent = `Global (${variantsCount} variantes)`;
        } else if (manageStock) {
            noteEl.textContent = "Stock simple";
        } else {
            noteEl.textContent = "Stock non géré";
        }
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const stopRealtime = subscribeToPrivateEvent(
        "admin.products",
        "products.stock.updated",
        (payload) => {
            updateProductStockNode(payload);
        }
    );

    window.addEventListener("beforeunload", () => {
        if (typeof stopRealtime === "function") {
            stopRealtime();
        }
    });
});

