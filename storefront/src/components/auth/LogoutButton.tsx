"use client";

import { useRouter } from "next/navigation";
import { logger } from "@/lib/logger";

interface LogoutButtonProps {
  children: React.ReactNode;
  className?: string;
}

export function LogoutButton({ children, className }: LogoutButtonProps) {
  const router = useRouter();

  const handleLogout = async (e: React.MouseEvent) => {
    e.preventDefault();

    try {
      const response = await fetch("/auth/logout", {
        method: "POST",
      });

      // Dispatch event before redirect
      window.dispatchEvent(new Event("auth:changed"));

      if (response.redirected) {
        window.location.href = response.url;
      } else if (response.ok) {
        router.push("/");
      }
    } catch (error) {
      logger.error("Logout error:", error);
      // Even on error, try to clear client state
      window.dispatchEvent(new Event("auth:changed"));
      router.push("/");
    }
  };

  return (
    <button onClick={handleLogout} className={className}>
      {children}
    </button>
  );
}
