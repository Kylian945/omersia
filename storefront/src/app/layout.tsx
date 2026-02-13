// src/app/layout.tsx
import type { Metadata } from "next";
import { CartProvider } from "@/components/cart/CartContext";
import "./globals.css";
import { CartDrawer } from "@/components/cart/CartDrawer";
import { ThemeProvider } from "@/components/theme/ThemeProvider";
import { ApiKeyWarningModal } from "@/components/common/ApiKeyWarningModal";
import { CookieConsentBanner } from "@/components/gdpr";
import { ModuleSystemInitializer } from "@/components/ModuleSystemInitializer";
import { getThemeSettings } from "@/lib/api-theme";
import { AuthProvider } from "@/contexts/AuthContext";
import { cookies } from "next/headers";
import { safeDecodeURIComponent } from "@/lib/utils/error-utils";

const BACKEND_URL = process.env.API_INTERNAL_URL;
const API_KEY = process.env.FRONT_API_KEY;

async function getInitialUser() {
  const cookieStore = await cookies();
  const rawToken = cookieStore.get("auth_token")?.value;
  const token = rawToken ? safeDecodeURIComponent(rawToken) : null;

  if (!token) return null;

  try {
    const res = await fetch(`${BACKEND_URL}/auth/me`, {
      method: "GET",
      headers: {
        "X-API-KEY": API_KEY || "",
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
      cache: "no-store",
    });

    if (!res.ok) return null;

    const user = await res.json();
    // Backend returns user directly: { id, firstname, lastname, email }
    return user?.id ? user : null;
  } catch {
    return null;
  }
}

export const metadata: Metadata = {
  title: "Omersia — Votre boutique moderne",
  description:
    "Une expérience e-commerce moderne, rapide et élégante, inspirée par Shopify.",
};

function hasApiKey(): boolean {
  const apiKey = process.env.FRONT_API_KEY;
  return !!apiKey && apiKey.trim().length > 0;
}

export default async function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const apiKeyPresent = hasApiKey();

  const [themeSettings, initialUser] = await Promise.all([
    getThemeSettings(),
    getInitialUser(),
  ]);

  const cartType = themeSettings.settings.cart?.cart_type || 'drawer';

  return (
    <html lang="fr" suppressHydrationWarning>
      <head>
        <ThemeProvider />
      </head>
      <body className="antialiased" suppressHydrationWarning>
        <div className="min-h-screen flex flex-col relative">
          <ModuleSystemInitializer />
          <AuthProvider initialUser={initialUser}>
            <CartProvider cartType={cartType}>
              {children}
              <CookieConsentBanner/>
              <CartDrawer />
            </CartProvider>
          </AuthProvider>
          <ApiKeyWarningModal hasApiKey={apiKeyPresent} />
        </div>
      </body>
    </html>
  );
}
