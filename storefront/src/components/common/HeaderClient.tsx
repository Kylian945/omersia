"use client";

import Link from "next/link";
import Image from "next/image";
import { usePathname } from "next/navigation";
import { useState, useEffect } from "react";
import { Container } from "./Container";
import { ShoppingBag, User, ChevronDown, Search, X, Menu } from "lucide-react";
import { useCart } from "@/components/cart/CartContext";
import { MobileMenu } from "./MobileMenu";
import { SearchDropdown } from "@/components/search/SearchDropdown";
import { CategoryNode, MenuItem, MenuResponse, ShopInfo } from "@/lib/types/menu-types";
import { ModuleHooks } from "@/components/modules/ModuleHooks";
import { useAuth } from "@/contexts/AuthContext";


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
  const [activeMegaId, setActiveMegaId] = useState<number | null>(null);
  const [searchOpen, setSearchOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [mounted, setMounted] = useState(false);

  const { items, openCart } = useCart();
  const { user, isAuthenticated } = useAuth();
  const cartCount = mounted ? items.reduce((sum, item) => sum + item.qty, 0) : 0;

  useEffect(() => { setMounted(true); }, []);

  const navItems: MenuItem[] =
    menu?.items?.filter(
      (item) => item.type !== "text" && (item.url || item.category)
    ) || [];

  const simpleFallback = !menu || !navItems.length;

  useEffect(() => {
    setActiveMegaId(null);
  }, [pathname]);

  useEffect(() => {
    if (searchOpen) {
      setActiveMegaId(null);
    }
  }, [searchOpen]);

  const toggleMegaMenu = (itemId: number) => {
    setActiveMegaId((prev) => (prev === itemId ? null : itemId));
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
        <div className="flex h-16 items-center justify-between gap-6">
          {searchOpen ? (
            <>
              {/* Barre de recherche */}
              <div className="flex-1 relative">
                <form onSubmit={handleSearchSubmit} className="flex items-center gap-3">
                  <Search className="w-5 h-5 text-neutral-400" />
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Rechercher des produits..."
                    className="flex-1 bg-transparent border-none outline-none text-base placeholder:text-neutral-400"
                    autoFocus
                  />
                  <button
                    type="button"
                    onClick={() => {
                      setSearchOpen(false);
                      setSearchQuery("");
                    }}
                    className="inline-flex h-9 w-9 items-center justify-center rounded-full hover:bg-neutral-100 transition"
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
                  className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-black/10 hover:border-black/40 transition md:hidden"
                  aria-label="Menu"
                >
                  <Menu className="w-4 h-4" />
                </button>

                {/* Logo */}
                <Link href="/" className="flex items-center gap-2">
                  {shopInfo.logo_url ? (
                    <Image
                      src={shopInfo.logo_url}
                      alt={shopInfo.display_name}
                      width={120}
                      height={32}
                      className="h-8 w-auto object-contain"
                      priority
                    />
                  ) : (
                    <div className="h-6 w-6 rounded-full bg-black text-white flex items-center justify-center font-bold">
                      {shopInfo.display_name?.[0]?.toUpperCase() || "S"}
                    </div>
                  )}
                  <span className="text-lg font-semibold tracking-tight">
                    {shopInfo.display_name}
                  </span>
                </Link>
              </div>

              {/* Navigation */}
              <nav className="hidden items-center gap-6 text-sm text-neutral-700 md:flex">
                {simpleFallback
                  ? (
                    <p className="text-xs text-gray-400 px-6 py-2 border border-dashed rounded-md border-gray-300">Menu principal (Ajouter des éléments dans l'administration)
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
                              "transition-colors hover:text-black " +
                              (isActive ? "text-black font-medium" : "")
                            }
                          >
                            {item.label}
                          </Link>
                        ) : (
                          <button
                            onClick={() =>
                              hasChildren
                                ? toggleMegaMenu(item.id)
                                : setActiveMegaId(null)
                            }
                            className={
                              "flex items-center gap-1 transition-colors hover:text-black " +
                              (isActive ? "text-black font-medium" : "")
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
                  onClick={() => setSearchOpen(true)}
                  className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-black/10 hover:border-black/40 transition"
                  aria-label="Rechercher"
                >
                  <Search className="w-4 h-4" />
                </button>

                {isAuthenticated ? (
                  <Link
                    href="/account"
                    className="rounded-full border border-black/10 inline-flex items-center gap-2 px-3 h-9 text-xs font-medium hover:border-black/40 transition"
                  >
                    <div className="flex items-center justify-center h-5 w-5 rounded-full bg-black text-white text-xxxs">
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
                    className="rounded-full border border-black/10 inline-flex h-9 w-9 items-center justify-center text-xs font-medium hover:border-black/40 transition"
                  >
                    <User className="w-4 h-4" />
                  </Link>
                )}

                {cartType === 'page' ? (
                  <Link
                    href="/cart"
                    className="relative inline-flex h-9 w-9 items-center justify-center rounded-full border border-black/10 hover:border-black/40"
                    aria-label="Panier"
                  >
                    <ShoppingBag className="w-4 h-4" />
                    <span className="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-black text-xxxs font-semibold text-white">
                      {cartCount}
                    </span>
                  </Link>
                ) : (
                  <button
                    onClick={openCart}
                    className="relative inline-flex h-9 w-9 items-center justify-center rounded-full border border-black/10 hover:border-black/40"
                    aria-label="Panier"
                  >
                    <ShoppingBag className="w-4 h-4" />
                    <span className="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-black text-xxxs font-semibold text-white">
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
            <div className="left-0 right-0 top-full border-b border-black/5 shadow-sm animate-fade-in">
              <Container>
                <div className="py-4">
                  <div className="mb-2 text-xs font-semibold text-neutral-500 uppercase tracking-[.18em]">
                    {activeItem.label}
                  </div>

                  <div className="grid gap-4 grid-cols-2 sm:grid-cols-3 md:grid-cols-4 mt-3">
                    {level2.map((child) => {
                      const hasLevel3 =
                        child.children && child.children.length > 0;

                      return (
                        <div
                          key={child.id}
                          className="group rounded-xl px-3 py-2 hover:bg-neutral-50 transition self-start"
                        >
                          <Link
                            href={getCategoryHref(child)}
                            className="block text-xs font-semibold text-neutral-900 group-hover:text-black"
                          >
                            {child.name || "Sans nom"}
                          </Link>

                          {hasLevel3 && (
                            <div className="mt-3 flex flex-col gap-2 ml-1">
                              {child.children!.map((grandChild) => (
                                <Link
                                  key={grandChild.id}
                                  href={getCategoryHref(grandChild)}
                                  className="text-xs text-neutral-500 hover:text-neutral-900"
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
