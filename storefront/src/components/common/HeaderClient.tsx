"use client";

import Link from "next/link";
import { OptimizedImage } from "@/components/common/OptimizedImage";
import { usePathname } from "next/navigation";
import { useState } from "react";
import { Container } from "./Container";
import { ShoppingBag, User, ChevronDown, Search, X, Menu } from "lucide-react";
import { useCart } from "@/components/cart/CartContext";
import { MobileMenu } from "./MobileMenu";
import { SearchDropdown } from "@/components/search/SearchDropdown";
import { CategoryNode, MenuItem, MenuResponse, ShopInfo } from "@/lib/types/menu-types";
import { ModuleHooks } from "@/components/modules/ModuleHooks";
import { useAuth } from "@/contexts/AuthContext";
import { useHydrated } from "@/hooks/useHydrated";


function getCategoryHref(node: CategoryNode): string {
  if (node.slug) return `/categories/${node.slug}`;
  return "#";
}

function getItemHref(item: MenuItem): string {
  if (item.url) return item.url;
  if (item.type === "category" && item.category?.slug) {
    return `/categories/${item.category.slug}`;
  }
  return "#";
}

export function HeaderClient({
  menu,
  shopInfo,
  cartType = 'drawer',
}: {
  menu: MenuResponse;
  shopInfo: ShopInfo;
  cartType?: string;
}) {
  const pathname = usePathname();
  const pathKey = pathname ?? "/";
  const [activeMegaByPath, setActiveMegaByPath] = useState<Record<string, number | null>>({});
  const [searchOpen, setSearchOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const isHydrated = useHydrated();

  const { items, openCart } = useCart();
  const { user, isAuthenticated } = useAuth();
  const activeMegaId = activeMegaByPath[pathKey] ?? null;
  const cartCount = isHydrated ? items.reduce((sum, item) => sum + item.qty, 0) : 0;

  const navItems: MenuItem[] =
    menu?.items?.filter(
      (item) => item.type !== "text" && (item.url || item.category)
    ) || [];

  const simpleFallback = !menu || !navItems.length;

  const toggleMegaMenu = (itemId: number) => {
    setActiveMegaByPath((prev) => ({
      ...prev,
      [pathKey]: prev[pathKey] === itemId ? null : itemId,
    }));
  };

  const closeMegaMenu = () => {
    setActiveMegaByPath((prev) => {
      if (prev[pathKey] === null || prev[pathKey] === undefined) {
        return prev;
      }

      return {
        ...prev,
        [pathKey]: null,
      };
    });
  };

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      window.location.href = `/search?q=${encodeURIComponent(searchQuery)}`;
    }
  };

  return (
    <>
      {/* Mobile Menu - Outside of header structure */}
      <MobileMenu
        shopInfo={shopInfo}
        isOpen={mobileMenuOpen}
        onClose={() => setMobileMenuOpen(false)}
        menu={menu}
      />

      <Container>
        <div className="theme-header-inner flex h-16 items-center justify-between gap-6">
          {searchOpen ? (
            <>
              {/* Barre de recherche */}
              <div className="flex-1 relative">
                <form onSubmit={handleSearchSubmit} className="flex items-center gap-3">
                  <Search className="w-5 h-5 text-[var(--theme-muted-color,#6b7280)]" />
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Rechercher des produits..."
                    className="flex-1 px-3 bg-transparent border-none outline-none text-base placeholder:text-[var(--theme-muted-color,#6b7280)]"
                    autoFocus
                  />
                  <button
                    type="button"
                    onClick={() => {
                      setSearchOpen(false);
                      setSearchQuery("");
                    }}
                    className="theme-header-control inline-flex h-9 w-9 items-center justify-center rounded-full hover:bg-[var(--theme-input-bg,#ffffff)] transition"
                    aria-label="Fermer la recherche"
                  >
                    <X className="w-4 h-4" />
                  </button>
                </form>
                <SearchDropdown
                  query={searchQuery}
                  onClose={() => {
                    setSearchOpen(false);
                    setSearchQuery("");
                  }}
                />
              </div>
            </>
          ) : (
            <>
              <div className="flex items-center gap-4">
                {/* Mobile Menu Button */}
                <button
                  onClick={() => setMobileMenuOpen(true)}
                  className="theme-header-control inline-flex h-9 w-9 items-center justify-center rounded-full border border-[var(--theme-border-default,#e5e7eb)] hover:border-[var(--theme-border-hover,#111827)] transition md:hidden"
                  aria-label="Menu"
                >
                  <Menu className="w-4 h-4" />
                </button>

                {/* Logo */}
                <Link href="/" className="flex items-center gap-2">
                  {shopInfo.logo_url ? (
                    <OptimizedImage
                      src={shopInfo.logo_url}
                      alt={shopInfo.display_name}
                      width={120}
                      height={32}
                      className="h-8 w-auto object-contain"
                      priority
                      fallback={
                        <div className="theme-header-control h-9 w-9 rounded-xl text-xl bg-[var(--theme-primary,#111827)] text-[var(--theme-button-primary-text,#ffffff)] flex items-center justify-center font-bold">
                          {shopInfo.display_name?.[0]?.toUpperCase() || "O"}
                        </div>
                      }
                    />
                  ) : (
                    <div className="theme-header-control h-9 w-9 rounded-xl text-xl bg-[var(--theme-primary,#111827)] text-[var(--theme-button-primary-text,#ffffff)] flex items-center justify-center font-bold">
                      {shopInfo.display_name?.[0]?.toUpperCase() || "O"}
                    </div>
                  )}
                  <span className="text-lg font-semibold tracking-tight hidden md:block">
                    {shopInfo.display_name}
                  </span>
                </Link>
              </div>

              {/* Navigation */}
              <nav className="hidden items-center gap-6 text-sm text-[var(--theme-body-color,#374151)] md:flex">
                {simpleFallback
                  ? (
                    <p className="text-xs text-[var(--theme-muted-color,#6b7280)] px-6 py-2 border border-dashed rounded-md border-[var(--theme-border-default,#e5e7eb)]">Menu principal (Ajouter des éléments dans l&apos;administration)
                    </p>
                  )
                  : navItems.map((item) => {
                    const href = getItemHref(item);
                    const hasChildren =
                      item.type === "category" &&
                      item.category?.children &&
                      item.category.children.length > 0;

                    const isActive =
                      pathname === href ||
                      (href !== "/" && pathname?.startsWith(href));

                    const isOpen = activeMegaId === item.id;

                    return (
                      <div
                        key={item.id}
                        className="relative flex items-center"
                      >
                        {!hasChildren ? (
                          <Link
                            href={href}
                            className={
                              "transition-colors hover:text-[var(--theme-heading-color,#111827)] " +
                              (isActive ? "text-[var(--theme-heading-color,#111827)] font-medium" : "")
                            }
                          >
                            {item.label}
                          </Link>
                        ) : (
                          <button
                            onClick={() =>
                              hasChildren
                                ? toggleMegaMenu(item.id)
                                : closeMegaMenu()
                            }
                            className={
                              "flex items-center gap-1 transition-colors hover:text-[var(--theme-heading-color,#111827)] " +
                              (isActive ? "text-[var(--theme-heading-color,#111827)] font-medium" : "")
                            }
                          >
                            {item.label}
                            <ChevronDown
                              className={`w-3 h-3 transition-transform ${isOpen ? "rotate-180" : ""
                                }`}
                            />
                          </button>
                        )}
                      </div>
                    );
                  })}
              </nav>

              {/* Actions */}
              <div className="flex items-center gap-4">
                {/* Hook: header.navigation.extra - Permet d'ajouter des éléments supplémentaires dans la navigation */}
                <ModuleHooks
                  hookName="header.navigation.extra"
                  context={{
                    isAuthenticated,
                    userId: user?.id,
                  }}
                />

                <button
                  onClick={() => {
                    closeMegaMenu();
                    setSearchOpen(true);
                  }}
                  className="theme-header-control inline-flex h-9 w-9 items-center justify-center rounded-full border border-[var(--theme-border-default,#e5e7eb)] hover:border-[var(--theme-border-hover,#111827)] transition"
                  aria-label="Rechercher"
                >
                  <Search className="w-4 h-4" />
                </button>

                {isAuthenticated ? (
                  <Link
                    href="/account"
                    className="theme-header-control rounded-full border border-[var(--theme-border-default,#e5e7eb)] inline-flex items-center gap-2 px-3 h-9 text-xs font-medium hover:border-[var(--theme-border-hover,#111827)] transition"
                  >
                    <div className="theme-header-control flex items-center justify-center h-5 w-5 rounded-full bg-[var(--theme-primary,#111827)] text-[var(--theme-button-primary-text,#ffffff)] text-xxxs">
                      {(user?.firstname?.[0] ||
                        user?.email?.[0] ||
                        "U"
                      ).toUpperCase()}
                    </div>
                    <span className="hidden sm:inline">Mon compte</span>
                  </Link>
                ) : (
                  <Link
                    href="/login"
                    className="theme-header-control rounded-full border border-[var(--theme-border-default,#e5e7eb)] inline-flex h-9 w-9 items-center justify-center text-xs font-medium hover:border-[var(--theme-border-hover,#111827)] transition"
                  >
                    <User className="w-4 h-4" />
                  </Link>
                )}

                {cartType === 'page' ? (
                  <Link
                    href="/cart"
                    className="theme-header-control relative inline-flex h-9 w-9 items-center justify-center rounded-full border border-[var(--theme-border-default,#e5e7eb)] hover:border-[var(--theme-border-hover,#111827)]"
                    aria-label="Panier"
                  >
                    <ShoppingBag className="w-4 h-4" />
                    <span className="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-[var(--theme-primary,#111827)] text-xxxs font-semibold text-[var(--theme-button-primary-text,#ffffff)]">
                      {cartCount}
                    </span>
                  </Link>
                ) : (
                  <button
                    onClick={openCart}
                    className="theme-header-control relative inline-flex h-9 w-9 items-center justify-center rounded-full border border-[var(--theme-border-default,#e5e7eb)] hover:border-[var(--theme-border-hover,#111827)]"
                    aria-label="Panier"
                  >
                    <ShoppingBag className="w-4 h-4" />
                    <span className="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-[var(--theme-primary,#111827)] text-xxxs font-semibold text-[var(--theme-button-primary-text,#ffffff)]">
                      {cartCount}
                    </span>
                  </button>
                )}
              </div>
            </>
          )}
        </div>
      </Container>

      {/* Mega-menu */}
      {!simpleFallback &&
        activeMegaId !== null &&
        (() => {
          const activeItem = navItems.find(
            (item) =>
              item.id === activeMegaId &&
              item.type === "category" &&
              item.category?.children &&
              item.category.children.length > 0
          );
          if (!activeItem) return null;

          const level2 = activeItem.category?.children ?? [];

          return (
            <div className="left-0 right-0 top-full border-b border-[var(--theme-border-default,#e5e7eb)] shadow-sm animate-fade-in">
              <Container>
                <div className="py-4">
                  <div className="mb-2 text-xs font-semibold text-[var(--theme-muted-color,#6b7280)] uppercase tracking-[.18em]">
                    {activeItem.label}
                  </div>

                  <div className="grid gap-4 grid-cols-2 sm:grid-cols-3 md:grid-cols-4 mt-3">
                    {level2.map((child) => {
                      const hasLevel3 =
                        child.children && child.children.length > 0;

                      return (
                        <div
                          key={child.id}
                          className="group rounded-xl px-3 py-2 hover:bg-[var(--theme-page-bg,#f6f6f7)] transition self-start"
                        >
                          <Link
                            href={getCategoryHref(child)}
                            className="block text-xs font-semibold text-[var(--theme-heading-color,#111827)] group-hover:text-[var(--theme-heading-color,#111827)]"
                          >
                            {child.name || "Sans nom"}
                          </Link>

                          {hasLevel3 && (
                            <div className="mt-3 flex flex-col gap-2 ml-1">
                              {child.children!.map((grandChild) => (
                                <Link
                                  key={grandChild.id}
                                  href={getCategoryHref(grandChild)}
                                  className="text-xs text-[var(--theme-muted-color,#6b7280)] hover:text-[var(--theme-heading-color,#111827)]"
                                >
                                  {grandChild.name || "Sans nom"}
                                </Link>
                              ))}
                            </div>
                          )}
                        </div>
                      );
                    })}
                  </div>

                  {activeItem.url && (
                    <div className="mt-4">
                      <Link
                        href={activeItem.url}
                        className="text-xs underline underline-offset-2"
                      >
                        Tous les{" "}
                        <span className="lowercase">
                          {activeItem.label}
                        </span>
                      </Link>
                    </div>
                  )}
                </div>
              </Container>
            </div>
          );
        })()}
    </>
  );
}
