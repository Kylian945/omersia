"use client";

import { useState } from "react";
import { Button } from "@/components/common/Button";
import { Eye, EyeOff } from "lucide-react";
import { getErrorMessage } from "@/lib/utils/error-utils";

type AuthMode = "login" | "register";

type CheckoutAuthProps = {
  onAuthSuccess: () => void;
  initialEmail?: string;
};

export function CheckoutAuth({
  onAuthSuccess,
  initialEmail = "",
}: CheckoutAuthProps) {
  const [mode, setMode] = useState<AuthMode>("login");
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);

  // Formulaire login
  const [loginEmail, setLoginEmail] = useState(initialEmail);
  const [loginPassword, setLoginPassword] = useState("");

  // Formulaire register
  const [registerFirstName, setRegisterFirstName] = useState("");
  const [registerLastName, setRegisterLastName] = useState("");
  const [registerEmail, setRegisterEmail] = useState(initialEmail);
  const [registerPassword, setRegisterPassword] = useState("");
  const [registerPasswordConfirm, setRegisterPasswordConfirm] = useState("");

  const handleLoginSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const formData = new FormData();
      formData.append("email", loginEmail);
      formData.append("password", loginPassword);

      const res = await fetch("/auth/login", {
        method: "POST",
        credentials: "include",
        headers: {
          Accept: "application/json",
        },
        body: formData,
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        throw new Error(data.message || "Identifiants invalides.");
      }

      // Connexion réussie - afficher un message avant de recharger
      setError(null);
      setSuccess(true);

      // Dispatch event to update auth state
      window.dispatchEvent(new Event("auth:changed"));

      // Petit délai pour que l'utilisateur voie que c'est réussi
      setTimeout(() => {
        onAuthSuccess();
      }, 800);
    } catch (err: unknown) {
      setError(getErrorMessage(err) || "Erreur lors de la connexion.");
    } finally {
      setLoading(false);
    }
  };

  const handleRegisterSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    // Validations
    if (
      !registerFirstName.trim() ||
      !registerLastName.trim() ||
      !registerEmail.trim() ||
      !registerPassword.trim()
    ) {
      setError("Veuillez remplir tous les champs obligatoires.");
      return;
    }

    if (!registerEmail.includes("@")) {
      setError("Veuillez saisir une adresse email valide.");
      return;
    }

    if (registerPassword.length < 8) {
      setError("Le mot de passe doit contenir au moins 8 caractères.");
      return;
    }

    if (registerPassword !== registerPasswordConfirm) {
      setError("Les mots de passe ne correspondent pas.");
      return;
    }

    setLoading(true);

    try {
      const formData = new FormData();
      formData.append("firstname", registerFirstName);
      formData.append("lastname", registerLastName);
      formData.append("email", registerEmail);
      formData.append("password", registerPassword);
      formData.append("password_confirmation", registerPasswordConfirm);

      const res = await fetch("/auth/register", {
        method: "POST",
        credentials: "include",
        headers: {
          Accept: "application/json",
        },
        body: formData,
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        throw new Error(data.message || "Erreur lors de la création du compte.");
      }

      // Inscription réussie, l'utilisateur est connecté
      setError(null);
      setSuccess(true);

      // Dispatch event to update auth state
      window.dispatchEvent(new Event("auth:changed"));

      // Petit délai pour que l'utilisateur voie que c'est réussi
      setTimeout(() => {
        onAuthSuccess();
      }, 800);
    } catch (err: unknown) {
      setError(getErrorMessage(err) || "Erreur lors de la création du compte.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-4">
      <div>
        <h2 className="text-sm font-semibold text-[var(--theme-heading-color,#111827)]">
          1. Identité
        </h2>
        <p className="text-xxxs text-[var(--theme-muted-color,#6b7280)] mt-0.5">
          Pour continuer, veuillez vous identifier ou créer un compte
        </p>
      </div>

      {/* Tabs */}
      <div className="flex gap-1 p-1 bg-[var(--theme-input-bg,#ffffff)] rounded-lg">
        <button
          type="button"
          onClick={() => {
            setMode("login");
            setError(null);
          }}
          className={`flex-1 px-3 py-1.5 rounded-md text-xs font-medium transition ${mode === "login"
            ? "bg-[var(--theme-card-bg,#ffffff)] text-[var(--theme-heading-color,#111827)] shadow-sm"
            : "text-[var(--theme-muted-color,#6b7280)] hover:text-[var(--theme-heading-color,#111827)]"
            }`}
        >
          Connexion
        </button>
        <button
          type="button"
          onClick={() => {
            setMode("register");
            setError(null);
          }}
          className={`flex-1 px-3 py-1.5 rounded-md text-xs font-medium transition ${mode === "register"
            ? "bg-[var(--theme-card-bg,#ffffff)] text-[var(--theme-heading-color,#111827)] shadow-sm"
            : "text-[var(--theme-muted-color,#6b7280)] hover:text-[var(--theme-heading-color,#111827)]"
            }`}
        >
          Créer un compte
        </button>
      </div>

      {error && (
        <div className="text-xs text-rose-600 bg-rose-50 border border-rose-100 px-3 py-2 rounded-lg">
          {error}
        </div>
      )}

      {success && (
        <div className="text-xs text-green-600 bg-green-50 border border-green-100 px-3 py-2 rounded-lg">
          ✓ Connexion réussie ! Chargement de vos informations...
        </div>
      )}

      {/* Mode: Login */}
      {mode === "login" && (
        <form onSubmit={handleLoginSubmit} className="space-y-3">
          <div>
            <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
              Email *
            </label>
            <input
              type="email"
              value={loginEmail}
              onChange={(e) => setLoginEmail(e.target.value)}
              className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
              placeholder="vous@example.com"
              required
              autoComplete="email"
            />
          </div>

          <div>
            <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
              Mot de passe *
            </label>
            <div className="relative">
              <input
                type={showPassword ? "text" : "password"}
                value={loginPassword}
                onChange={(e) => setLoginPassword(e.target.value)}
                className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)] pr-10"
                placeholder="Votre mot de passe"
                required
                autoComplete="current-password"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--theme-muted-color,#6b7280)] hover:text-[var(--theme-muted-color,#6b7280)]"
              >
                {showPassword ? (
                  <EyeOff className="w-4 h-4" />
                ) : (
                  <Eye className="w-4 h-4" />
                )}
              </button>
            </div>
          </div>

          <div className="flex justify-end">
            <Button
              type="submit"
              size="md"
              variant="primary"
              className="inline-flex"
              disabled={loading}
            >
              {loading ? "Connexion..." : "Se connecter"}
            </Button>
          </div>

          <p className="text-xxxs text-[var(--theme-muted-color,#6b7280)] text-end">
            Votre panier sera conservé après connexion
          </p>
        </form>
      )}

      {/* Mode: Register */}
      {mode === "register" && (
        <form onSubmit={handleRegisterSubmit} className="space-y-3">
          <div className="grid grid-cols-2 gap-2">
            <div>
              <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                Prénom *
              </label>
              <input
                type="text"
                value={registerFirstName}
                onChange={(e) => setRegisterFirstName(e.target.value)}
                className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                placeholder="Prénom"
                required
                autoComplete="given-name"
              />
            </div>
            <div>
              <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
                Nom *
              </label>
              <input
                type="text"
                value={registerLastName}
                onChange={(e) => setRegisterLastName(e.target.value)}
                className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
                placeholder="Nom"
                required
                autoComplete="family-name"
              />
            </div>
          </div>

          <div>
            <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
              Email *
            </label>
            <input
              type="email"
              value={registerEmail}
              onChange={(e) => setRegisterEmail(e.target.value)}
              className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
              placeholder="vous@example.com"
              required
              autoComplete="email"
            />
          </div>

          <div>
            <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
              Mot de passe * (min. 8 caractères)
            </label>
            <div className="relative">
              <input
                type={showPassword ? "text" : "password"}
                value={registerPassword}
                onChange={(e) => setRegisterPassword(e.target.value)}
                className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)] pr-10"
                placeholder="Votre mot de passe"
                required
                minLength={8}
                autoComplete="new-password"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--theme-muted-color,#6b7280)] hover:text-[var(--theme-muted-color,#6b7280)]"
              >
                {showPassword ? (
                  <EyeOff className="w-4 h-4" />
                ) : (
                  <Eye className="w-4 h-4" />
                )}
              </button>
            </div>
          </div>

          <div>
            <label className="block text-xxxs text-[var(--theme-muted-color,#6b7280)] mb-1">
              Confirmer le mot de passe *
            </label>
            <input
              type={showPassword ? "text" : "password"}
              value={registerPasswordConfirm}
              onChange={(e) => setRegisterPasswordConfirm(e.target.value)}
              className="w-full rounded-lg border border-[var(--theme-border-default,#e5e7eb)] px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--theme-primary,#111827)]"
              placeholder="Confirmez votre mot de passe"
              required
              minLength={8}
              autoComplete="new-password"
            />
          </div>

          <div className="flex justify-end">
            <Button
              type="submit"
              size="md"
              variant="primary"
              className="inline-flex"
              disabled={loading}
            >
              {loading ? "Création..." : "Créer mon compte"}
            </Button>
          </div>
          <p className="text-xxxs text-[var(--theme-muted-color,#6b7280)] text-end">
            Votre panier sera conservé et associé à votre compte
          </p>
        </form>
      )}
    </div>
  );
}
