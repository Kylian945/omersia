"use client";

import Link from "next/link";
import { OptimizedImage } from "@/components/common/OptimizedImage";
import { Container } from "./Container";
import { ShopInfo } from "@/lib/types/menu-types";

export function HeaderAuth({ shopInfo }: { shopInfo: ShopInfo; }) {
    return (
        <>
            <header className="border-b border-neutral-100 bg-white/80 backdrop-blur">
                <Container>
                    <div className="h-16 flex items-center justify-between gap-4">
                        {/* Logo */}
                        <Link href="/" className="flex items-center gap-2">
                            {shopInfo.logo_url ? (
                                <OptimizedImage
                                    src={shopInfo.logo_url}
                                    alt={shopInfo.display_name}
                                    width={120}
                                    height={32}
                                    className="h-8 w-auto object-contain"
                                    fallback={
                                        <div className="h-9 w-9 text-xl rounded-xl bg-black text-white flex items-center justify-center font-bold">
                                            {shopInfo.display_name?.[0]?.toUpperCase() || "S"}
                                        </div>
                                    }
                                />
                            ) : (
                                <div className="h-9 w-9 text-xl rounded-xl bg-black text-white flex items-center justify-center font-bold">
                                    {shopInfo.display_name?.[0]?.toUpperCase() || "S"}
                                </div>
                            )}
                            <span className="text-lg font-semibold tracking-tight">
                                {shopInfo.display_name}
                            </span>
                        </Link>
                        <Link
                            href="/"
                            className="text-xs text-neutral-500 hover:text-neutral-900 transition"
                        >
                            Retour à la boutique
                        </Link>
                    </div>
                </Container>
            </header>
        </>
    );
}


