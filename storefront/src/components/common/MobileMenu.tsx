"use client";

import { useState, useEffect } from "react";
import { createPortal } from "react-dom";
import Link from "next/link";
import { X, ChevronRight, ChevronLeft } from "lucide-react";
import { CategoryNode, MenuItem, MenuResponse, ShopInfo } from "@/lib/types/menu-types";

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

type ActiveLevel = {
  level: number;
  item?: MenuItem | CategoryNode;
  parentLabel?: string;
};

export function MobileMenu({
  isOpen,
  onClose,
  menu,
  shopInfo
}: {
  isOpen: boolean;
  onClose: () => void;
  menu: MenuResponse;
  shopInfo: ShopInfo;
}) {
  const [activeLevel, setActiveLevel] = useState<ActiveLevel>({ level: 1 });
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  const navItems: MenuItem[] =
    menu?.items?.filter(
      (item) => item.type !== "text" && (item.url || item.category)
    ) || [];

  const handleItemClick = (item: MenuItem) => {
    const hasChildren =
      item.type === "category" &&
      item.category?.children &&
      item.category.children.length > 0;

    if (hasChildren) {
      setActiveLevel({
        level: 2,
        item,
        parentLabel: item.label,
      });
    } else {
      onClose();
    }
  };

  const handleLevel2Click = (child: CategoryNode, parentLabel: string) => {
    const hasChildren = child.children && child.children.length > 0;

    if (hasChildren) {
      setActiveLevel({
        level: 3,
        item: child,
        parentLabel,
      });
    } else {
      onClose();
    }
  };

  const goBack = () => {
    if (activeLevel.level === 3) {
      setActiveLevel({ level: 2, item: activeLevel.item });
    } else if (activeLevel.level === 2) {
      setActiveLevel({ level: 1 });
    }
  };

  const handleClose = () => {
    setActiveLevel({ level: 1 });
    onClose();
  };

  if (!isOpen || !mounted) return null;

  const menuContent = (
    <>
      {/* Overlay */}
      <div
        className="fixed inset-0 bg-black/50 z-100 md:hidden backdrop-blur-md"
        onClick={handleClose}
      />

      {/* Offcanvas */}
      <div className="fixed top-0 left-0 bottom-0 w-[85%] max-w-sm bg-white z-101 shadow-xl md:hidden overflow-hidden flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between px-4 h-16 border-b border-neutral-200">
          {activeLevel.level > 1 ? (
            <button
              onClick={goBack}
              className="flex items-center gap-2 text-sm font-medium"
            >
              <ChevronLeft className="w-4 h-4" />
              Retour
            </button>
          ) : (

            <Link href="/" className="flex items-center gap-2">
              {shopInfo.logo_url ? (
                <img
                  src={shopInfo.logo_url}
                  alt={shopInfo.display_name}
                  className="h-8 w-auto object-contain"
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
          )}
          <button
            onClick={handleClose}
            className="inline-flex h-9 w-9 items-center justify-center rounded-full hover:bg-neutral-100 transition"
            aria-label="Fermer le menu"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto">
          {/* Level 1 - Main menu items */}
          {activeLevel.level === 1 && (
            <nav className="py-2">
              {navItems.map((item) => {
                const href = getItemHref(item);
                const hasChildren =
                  item.type === "category" &&
                  item.category?.children &&
                  item.category.children.length > 0;

                return (
                  <div key={item.id}>
                    {hasChildren ? (
                      <button
                        onClick={() => handleItemClick(item)}
                        className="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-neutral-50 transition"
                      >
                        <span className="font-medium text-neutral-900">
                          {item.label}
                        </span>
                        <ChevronRight className="w-4 h-4 text-neutral-400" />
                      </button>
                    ) : (
                      <Link
                        href={href}
                        onClick={handleClose}
                        className="block px-4 py-3 font-medium text-neutral-900 hover:bg-neutral-50 transition"
                      >
                        {item.label}
                      </Link>
                    )}
                  </div>
                );
              })}
            </nav>
          )}

          {/* Level 2 - Subcategories */}
          {activeLevel.level === 2 && activeLevel.item && "category" in activeLevel.item && (
            <nav className="py-2">
              {/* Parent link */}
              {"category" in activeLevel.item && activeLevel.item.category?.slug && (
                <Link
                  href={getCategoryHref(activeLevel.item.category)}
                  onClick={handleClose}
                  className="block px-4 py-3 text-sm font-semibold text-black border-b border-neutral-100"
                >
                  Voir tous les {activeLevel.item.label.toLowerCase()}
                </Link>
              )}

              {/* Level 2 items */}
              {"category" in activeLevel.item && activeLevel.item.category?.children?.map((child) => {
                const hasChildren = child.children && child.children.length > 0;

                return (
                  <div key={child.id}>
                    {hasChildren ? (
                      <button
                        onClick={() => handleLevel2Click(child, activeLevel.parentLabel || "")}
                        className="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-neutral-50 transition"
                      >
                        <span className="text-neutral-900">
                          {child.name || "Sans nom"}
                        </span>
                        <ChevronRight className="w-4 h-4 text-neutral-400" />
                      </button>
                    ) : (
                      <Link
                        href={getCategoryHref(child)}
                        onClick={handleClose}
                        className="block px-4 py-3 text-neutral-900 hover:bg-neutral-50 transition"
                      >
                        {child.name || "Sans nom"}
                      </Link>
                    )}
                  </div>
                );
              })}
            </nav>
          )}

          {/* Level 3 - Sub-subcategories */}
          {activeLevel.level === 3 && activeLevel.item && "children" in activeLevel.item && (
            <nav className="py-2">
              {/* Parent link */}
              {"slug" in activeLevel.item && activeLevel.item.slug && (
                <Link
                  href={getCategoryHref(activeLevel.item as CategoryNode)}
                  onClick={handleClose}
                  className="block px-4 py-3 text-sm font-semibold text-black border-b border-neutral-100"
                >
                  Voir tous les {(activeLevel.item as CategoryNode).name?.toLowerCase()}
                </Link>
              )}

              {/* Level 3 items */}
              {(activeLevel.item as CategoryNode).children?.map((grandChild) => (
                <Link
                  key={grandChild.id}
                  href={getCategoryHref(grandChild)}
                  onClick={handleClose}
                  className="block px-4 py-3 text-neutral-900 hover:bg-neutral-50 transition"
                >
                  {grandChild.name || "Sans nom"}
                </Link>
              ))}
            </nav>
          )}
        </div>
      </div>
    </>
  );

  return createPortal(menuContent, document.body);
}
