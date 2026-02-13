"use client";

import Link from "next/link";
import { OptimizedImage } from "@/components/common/OptimizedImage";
import { Container } from "./Container";
import { HeaderWrapper } from "./HeaderWrapper";
import { ShopInfo } from "@/lib/types/menu-types";

export function HeaderAuth({ shopInfo }: { shopInfo: ShopInfo; }) {
    return (
        <HeaderWrapper>
            <Container>
                <div className="theme-header-inner h-16 flex items-center justify-between gap-4">
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
                                    <div className="theme-header-control h-9 w-9 text-xl rounded-xl bg-[var(--theme-primary,#111827)] text-[var(--theme-button-primary-text,#ffffff)] flex items-center justify-center font-bold">
                                        {shopInfo.display_name?.[0]?.toUpperCase() || "S"}
                                    </div>
                                }
                            />
                        ) : (
                            <div className="theme-header-control h-9 w-9 text-xl rounded-xl bg-[var(--theme-primary,#111827)] text-[var(--theme-button-primary-text,#ffffff)] flex items-center justify-center font-bold">
                                {shopInfo.display_name?.[0]?.toUpperCase() || "S"}
                            </div>
                        )}
                        <span className="text-lg font-semibold tracking-tight">
                            {shopInfo.display_name}
                        </span>
                    </Link>
                    <Link
                        href="/"
                        className="text-xs text-[var(--theme-muted-color,#6b7280)] hover:text-[var(--theme-heading-color,#111827)] transition"
                    >
                        Retour à la boutique
                    </Link>
                </div>
            </Container>
        </HeaderWrapper>
    );
}
