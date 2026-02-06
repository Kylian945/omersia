"use client";

import {
  createContext,
  useContext,
  useState,
  useEffect,
  useRef,
  useCallback,
  ReactNode,
} from "react";
import { CartItem } from "@/lib/types/product-types";
import { AddToCartModal } from "./AddToCartModal";
import { useRouter } from "next/navigation";
import { logger } from "@/lib/logger";

// Debounce delay for localStorage and backend sync (ms)
const SYNC_DEBOUNCE_MS = 300;

// Re-export CartItem pour compatibilité
export type { CartItem };

type CartContextType = {
  items: CartItem[];
  addItem: (item: CartItem, showModal?: boolean) => void;
  removeItem: (index: number) => void;
  updateQty: (index: number, qty: number) => void;
  clear: () => void;
  isOpen: boolean;
  openCart: () => void;
  closeCart: () => void;
  toggleCart: () => void;
  totalQty: number;
  subtotal: number;
  cartToken: string | null;
  cartId: number | null;
  cartType: string;
  isHydrated: boolean;
};

const CartContext = createContext<CartContextType | undefined>(undefined);

const CART_STORAGE_KEY = "omersia_cart_items";
const CART_TOKEN_STORAGE_KEY = "omersia_cart_token";

export function CartProvider({
  children,
  cartType = 'drawer'
}: {
  children: ReactNode;
  cartType?: string;
}) {
  const router = useRouter();

  // Lazy initialization - load from localStorage only once during initial render
  const [items, setItems] = useState<CartItem[]>(() => {
    if (typeof window === "undefined") return [];

    try {
      const raw = window.localStorage.getItem(CART_STORAGE_KEY);
      if (raw) {
        const parsed = JSON.parse(raw) as CartItem[];
        if (Array.isArray(parsed)) {
          return parsed;
        }
      }
    } catch (e) {
      logger.error("Failed to parse cart from localStorage", e);
    }

    return [];
  });

  const [cartToken, setCartToken] = useState<string | null>(() => {
    if (typeof window === "undefined") return null;

    try {
      return window.localStorage.getItem(CART_TOKEN_STORAGE_KEY);
    } catch {
      return null;
    }
  });

  const [isOpen, setIsOpen] = useState(false);
  const [isHydrated, setIsHydrated] = useState(false);
  const [cartId, setCartId] = useState<number | null>(null);
  const [showAddToCartModal, setShowAddToCartModal] = useState(false);
  const [lastAddedItem, setLastAddedItem] = useState<CartItem | null>(null);

  // 1) Mark as hydrated after mount
  useEffect(() => {
    setIsHydrated(true);
  }, []);

  // 2) Sauvegarde dans localStorage à chaque changement d'items (debounced)
  const localStorageTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  useEffect(() => {
    if (!isHydrated) return;

    // Clear previous timeout
    if (localStorageTimeoutRef.current) {
      clearTimeout(localStorageTimeoutRef.current);
    }

    // Debounce localStorage writes to avoid blocking main thread
    localStorageTimeoutRef.current = setTimeout(() => {
      try {
        window.localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items));
      } catch (e) {
        logger.error("Failed to save cart to localStorage", e);
      }
    }, SYNC_DEBOUNCE_MS);

    return () => {
      if (localStorageTimeoutRef.current) {
        clearTimeout(localStorageTimeoutRef.current);
      }
    };
  }, [items, isHydrated]);

  // 3) Sync avec le backend Laravel (debounced)
  const backendSyncTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  useEffect(() => {
    if (!isHydrated) return;

    // Si aucun item et pas de token, pas la peine de sync
    if (!items.length && !cartToken) return;

    // Clear previous timeout
    if (backendSyncTimeoutRef.current) {
      clearTimeout(backendSyncTimeoutRef.current);
    }

    let abort = false;

    const sync = async () => {
      try {
        const res = await fetch("/api/cart/sync", {
          method: "POST",
          credentials: "include",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify({
            token: cartToken,
            items,
            // éventuellement: email, currency...
          }),
        });


        if (!res.ok) {
          const text = await res.text().catch(() => "");
          logger.error(`Cart sync failed: ${res.status} - ${text}`);
          return;
        }

        const data: {
          id?: number;
          token?: string;
          subtotal?: number;
          total_qty?: number;
          deleted?: boolean;
        } = await res.json();

        if (abort) return;

        if (data.deleted) {
          setCartToken(null);
          setCartId(null);
          setItems([]); // déjà vide, mais on s'assure

          try {
            window.localStorage.removeItem(CART_STORAGE_KEY);
            window.localStorage.removeItem(CART_TOKEN_STORAGE_KEY);
          } catch (e) {
            logger.error("Failed to clear cart localStorage", e);
          }

          return;
        }

        if (data.id) {
          setCartId(data.id);
        }

        if (data.token && data.token !== cartToken) {
          setCartToken(data.token);
          try {
            window.localStorage.setItem(CART_TOKEN_STORAGE_KEY, data.token);
          } catch (e) {
            logger.error("Failed to save cart token", e);
          }
        }
      } catch (e) {
        logger.error("Cart sync error", e);
      }
    };

    // Debounce backend sync to batch rapid changes
    backendSyncTimeoutRef.current = setTimeout(sync, SYNC_DEBOUNCE_MS);

    return () => {
      abort = true;
      if (backendSyncTimeoutRef.current) {
        clearTimeout(backendSyncTimeoutRef.current);
      }
    };
  }, [items, cartToken, isHydrated]);

  // Helper to create a unique key for cart items
  const getItemKey = useCallback((item: CartItem) =>
    `${item.id}-${item.variantId ?? 'none'}-${item.variantLabel ?? 'none'}`,
  []);

  const addItem = (item: CartItem, showModal: boolean = true) => {
    setItems((prev) => {
      // Build a Map for O(1) lookup instead of O(n) findIndex
      const itemKey = getItemKey(item);
      const existingIndex = prev.findIndex((p) => getItemKey(p) === itemKey);

      if (existingIndex !== -1) {
        const next = [...prev];
        next[existingIndex] = {
          ...next[existingIndex],
          qty: next[existingIndex].qty + item.qty,
        };
        return next;
      }

      return [...prev, item];
    });

    // Comportement selon le type de panier configuré
    if (showModal) {
      if (cartType === 'page') {
        // Mode page : afficher uniquement la modal de confirmation
        setLastAddedItem(item);
        setShowAddToCartModal(true);
      } else {
        // Mode drawer : ouvrir uniquement le drawer
        setIsOpen(true);
      }
    }
  };

  const removeItem = (index: number) => {
    setItems((prev) => prev.filter((_, i) => i !== index));
  };

  const updateQty = (index: number, qty: number) => {
    if (qty <= 0) {
      removeItem(index);
      return;
    }
    setItems((prev) =>
      prev.map((item, i) => (i === index ? { ...item, qty } : item))
    );
  };

  const clear = () => {
    setItems([]);
    // Optionnel : tu peux garder le token pour tracker un panier vide
  };

  const openCart = () => setIsOpen(true);
  const closeCart = () => setIsOpen(false);
  const toggleCart = () => setIsOpen((v) => !v);

  const totalQty = items.reduce((sum, item) => sum + item.qty, 0);
  const subtotal = items.reduce(
    (sum, item) => sum + item.qty * item.price,
    0
  );

  const handleViewCart = () => {
    setShowAddToCartModal(false);
    router.push('/cart');
  };

  return (
    <CartContext.Provider
      value={{
        items,
        addItem,
        removeItem,
        updateQty,
        clear,
        isOpen,
        openCart,
        closeCart,
        toggleCart,
        totalQty,
        subtotal,
        cartToken,
        cartId,
        cartType,
        isHydrated,
      }}
    >
      {children}

      {/* Modal de confirmation d'ajout au panier (mode page uniquement) */}
      {cartType === 'page' && (
        <AddToCartModal
          isOpen={showAddToCartModal}
          onClose={() => setShowAddToCartModal(false)}
          item={lastAddedItem}
          onViewCart={handleViewCart}
        />
      )}
    </CartContext.Provider>
  );
}

export function useCart() {
  const ctx = useContext(CartContext);
  if (!ctx) {
    throw new Error("useCart must be used within a CartProvider");
  }
  return ctx;
}
