import { NextResponse } from "next/server";
import { cookies } from "next/headers";
import {
  createOrder,
  type CheckoutOrderPayload,
  type OrderItemInput,
  getAddressById,
  OrderApi,
  updateOrder,
  apiJson,
} from "@/lib/api";
import type { CartItem } from "@/components/cart/CartContext";
import type { AuthUser } from "@/lib/types/user-types";
import { logger } from "@/lib/logger";

type FrontCheckoutPayload = {
  cartId?: number;
  orderId?: number;
  shippingMethodId: number;
  shippingAddressId: number;
  billingAddressId?: number;
  items: CartItem[];
  subtotal: number;
  shippingCostBase: number;
  shippingDiscountTotal: number;
  shippingCost: number;
  promoDiscount: number;
  automaticDiscountTotal: number;
  taxTotal: number;
  total: number;
};

export async function POST(req: Request) {
  try {
    const cookieStore = await cookies();
    const token = cookieStore.get("auth_token")?.value;

    if (!token) {
      return NextResponse.json({ message: "Unauthorized" }, { status: 401 });
    }

    // Récupérer l'utilisateur depuis le backend Laravel
    const { res: meRes, data: user } = await apiJson<AuthUser>(
      "/auth/me",
      {
        extraHeaders: {
          Authorization: `Bearer ${token}`,
        },
      }
    );

    if (meRes.status === 401) {
      return NextResponse.json({ message: "Unauthorized" }, { status: 401 });
    }

    if (!meRes.ok || !user) {
      logger.warn("Checkout order auth verification unavailable", {
        status: meRes.status,
      });

      return NextResponse.json(
        {
          message:
            "Le service d'authentification est temporairement indisponible. Veuillez réessayer.",
        },
        { status: 503 }
      );
    }

    const body = (await req.json()) as FrontCheckoutPayload;

    if (!body.items || body.items.length === 0) {
      return NextResponse.json({ message: "Panier vide." }, { status: 422 });
    }

    if (!body.shippingAddressId) {
      return NextResponse.json(
        { message: "Adresse de livraison manquante." },
        { status: 422 }
      );
    }

    if (!body.shippingMethodId) {
      return NextResponse.json(
        { message: "Mode de livraison manquant." },
        { status: 422 }
      );
    }

    // 1) On va chercher l’adresse côté backend (pas dans le payload du browser)
    const shippingAddress = await getAddressById(body.shippingAddressId, token);
    if (!shippingAddress) {
      return NextResponse.json(
        { message: "Adresse de livraison introuvable." },
        { status: 422 }
      );
    }

    const billingAddressId = body.billingAddressId ?? body.shippingAddressId;
    const billingAddress =
      billingAddressId === body.shippingAddressId
        ? shippingAddress
        : await getAddressById(billingAddressId, token);

    if (!billingAddress) {
      return NextResponse.json(
        { message: "Adresse de facturation introuvable." },
        { status: 422 }
      );
    }

    // 2) Construction du payload pour Laravel (100% server-side)
    const items: OrderItemInput[] = body.items.map((item) => ({
      product_id: item.id,
      variant_id: item.variantId,
      name: item.name,
      sku: undefined,
      quantity: item.qty,
      unit_price: item.price,
      total_price: item.price * item.qty,
    }));

    const discountTotal =
      (body.promoDiscount ?? 0) +
      (body.automaticDiscountTotal ?? 0) +
      (body.shippingDiscountTotal ?? 0);

    const payload: CheckoutOrderPayload = {
      cart_id: body.cartId,
      currency: "EUR",
      shipping_method_id: body.shippingMethodId,

      customer_email: user.email,
      customer_firstname: user.firstname ?? null,
      customer_lastname: user.lastname ?? null,

      shipping_address: {
        line1: shippingAddress.line1,
        line2: shippingAddress.line2 ?? null,
        postcode: shippingAddress.postcode,
        city: shippingAddress.city,
        country: shippingAddress.country,
        phone: shippingAddress.phone ?? null,
      },

      billing_address: {
        line1: billingAddress.line1,
        line2: billingAddress.line2 ?? null,
        postcode: billingAddress.postcode,
        city: billingAddress.city,
        country: billingAddress.country,
        phone: billingAddress.phone ?? null,
      },

      items,

      // 🧮 cohérent avec tes calculs front
      discount_total: discountTotal,
      shipping_total: body.shippingCost, // déjà net de remises
      tax_total: body.taxTotal || 0,
      total: body.total,
    };

    let order: OrderApi | null = null;

    if (body.orderId) {
      // 🔄 Update
      order = await updateOrder(body.orderId, payload, token);
    } else {
      // 🆕 Create
      order = await createOrder(payload, token);
    }
    if (!order) {
      return NextResponse.json(
        { message: "Erreur lors de la création de la commande." },
        { status: 500 }
      );
    }

    return NextResponse.json(
      {
        id: order.id,
        number: order.number,
      },
      { status: 201 }
    );
  } catch (error) {
    logger.error("Checkout order error:", error);
    return NextResponse.json(
      { message: "An error occurred while processing your order" },
      { status: 500 }
    );
  }
}
