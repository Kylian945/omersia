"use client";

import { createContext, useContext, useState, useEffect, useCallback, ReactNode } from "react";
import { AuthUser } from "@/lib/types/user-types";
import { logger } from "@/lib/logger";

interface AuthContextType {
  user: AuthUser | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

interface AuthProviderProps {
  children: ReactNode;
  initialUser?: AuthUser | null;
}

export function AuthProvider({ children, initialUser = null }: AuthProviderProps) {
  const [user, setUser] = useState<AuthUser | null>(initialUser);
  const [isLoading, setIsLoading] = useState(false);
  const [isInitialized, setIsInitialized] = useState(false);

  const refreshUser = useCallback(async () => {
    setIsLoading(true);

    try {
      // Note: auth_token cookie is httpOnly, so we can't check it client-side
      // We simply call /auth/me which will read the cookie server-side
      const res = await fetch("/auth/me", {
        credentials: "include",
        cache: "no-store",
        headers: {
          "Cache-Control": "no-store",
          Accept: "application/json",
        },
      });

      if (!res.ok) {
        // Only clear local auth state when the API explicitly says the token is invalid.
        if (res.status === 401 || res.status === 403) {
          setUser(null);
        }
        return;
      }

      const data = (await res.json().catch(() => null)) as
        | { authenticated?: boolean; user?: AuthUser | null; unavailable?: boolean }
        | null;

      if (data?.unavailable) {
        return;
      }

      if (data?.authenticated && data.user) {
        setUser(data.user);
      } else {
        setUser(null);
      }
    } catch (error) {
      logger.error("Error fetching user:", error);
      // Keep the current user on transient failures.
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Initialize on mount - only fetch if no initialUser provided
  useEffect(() => {
    if (!isInitialized) {
      setIsInitialized(true);

      // If we don't have an initialUser from SSR, check for cookie
      if (!initialUser) {
        refreshUser();
      }
    }
  }, [isInitialized, initialUser, refreshUser]);

  // Listen for auth:changed events
  useEffect(() => {
    const handleAuthChanged = () => {
      refreshUser();
    };

    window.addEventListener("auth:changed", handleAuthChanged);

    return () => {
      window.removeEventListener("auth:changed", handleAuthChanged);
    };
  }, [refreshUser]);

  const value: AuthContextType = {
    user,
    isAuthenticated: !!user,
    isLoading,
    refreshUser,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextType {
  const context = useContext(AuthContext);

  if (context === undefined) {
    throw new Error("useAuth must be used within AuthProvider");
  }

  return context;
}
