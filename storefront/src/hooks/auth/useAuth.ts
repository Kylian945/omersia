"use client";

import { useAuth as useAuthContext } from "@/contexts/AuthContext";

/**
 * Hook for accessing authentication state.
 * This is now a wrapper around the AuthContext for backward compatibility.
 *
 * @deprecated Use `useAuth` from `@/contexts/AuthContext` directly.
 */
export function useAuth() {
  const context = useAuthContext();

  return {
    user: context.user,
    loading: context.isLoading,
    isAuthenticated: context.isAuthenticated,
    refresh: context.refreshUser,
  };
}
