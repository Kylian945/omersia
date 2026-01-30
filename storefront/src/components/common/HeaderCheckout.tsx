"use client";

import Link from "next/link";
import Image from "next/image";
import { Container } from "./Container";
import { ShopInfo } from "@/lib/types/menu-types";

export function HeaderCheckout({ shopInfo }: { shopInfo: ShopInfo; }) {

  return (
    <>
      <header className="sticky top-0 z-40 border-b border-black/5 bg-white/80 backdrop-blur">
        <Container>
          <div className="flex h-16 items-center justify-between gap-6">
            {/* Logo */}
            <Link href="/" className="flex items-center gap-2">
              {shopInfo.logo_url ? (
                <Image
                  src={shopInfo.logo_url}
                  alt={shopInfo.display_name}
                  width={120}
                  height={32}
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
          </div>
        </Container>
      </header>
    </>
  );
}
