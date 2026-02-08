// app/auth/password/reset/route.ts
import { NextRequest, NextResponse } from "next/server";

const BACKEND_URL = process.env.API_INTERNAL_URL;
const FRONTEND_URL = process.env.NEXT_PUBLIC_FRONTEND_URL || "http://localhost:8000";
const API_KEY = process.env.FRONT_API_KEY;

export async function POST(req: NextRequest) {
  const formData = await req.formData();

  const token = String(formData.get("token") || "");
  const email = String(formData.get("email") || "");
  const password = String(formData.get("password") || "");
  const passwordConfirmation = String(formData.get("password_confirmation") || "");

  // Validation côté serveur
  if (!token || !email || !password || !passwordConfirmation) {
    const url = new URL("/password/reset", FRONTEND_URL);
    url.searchParams.set("token", token);
    url.searchParams.set("email", email);
    url.searchParams.set(
      "error",
      encodeURIComponent("Tous les champs sont requis.")
    );
    return NextResponse.redirect(url);
  }

  if (password !== passwordConfirmation) {
    const url = new URL("/password/reset", FRONTEND_URL);
    url.searchParams.set("token", token);
    url.searchParams.set("email", email);
    url.searchParams.set(
      "error",
      encodeURIComponent("Les mots de passe ne correspondent pas.")
    );
    return NextResponse.redirect(url);
  }

  if (password.length < 8) {
    const url = new URL("/password/reset", FRONTEND_URL);
    url.searchParams.set("token", token);
    url.searchParams.set("email", email);
    url.searchParams.set(
      "error",
      encodeURIComponent("Le mot de passe doit contenir au moins 8 caractères.")
    );
    return NextResponse.redirect(url);
  }

  try {
    const res = await fetch(`${BACKEND_URL}/auth/password/reset`, {
      method: "POST",
      headers: {
        "X-API-KEY": API_KEY || "",
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({
        token,
        email,
        password,
        password_confirmation: passwordConfirmation,
      }),
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      const message =
        data?.message || "Le lien de réinitialisation est invalide ou a expiré.";
      const url = new URL("/password/reset", FRONTEND_URL);
      url.searchParams.set("token", token);
      url.searchParams.set("email", email);
      url.searchParams.set("error", encodeURIComponent(message));
      return NextResponse.redirect(url);
    }

    // Succès : rediriger vers la page de connexion avec un message
    const url = new URL("/login", FRONTEND_URL);
    url.searchParams.set(
      "success",
      encodeURIComponent("Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.")
    );
    return NextResponse.redirect(url);
  } catch {
    const url = new URL("/password/reset", FRONTEND_URL);
    url.searchParams.set("token", token);
    url.searchParams.set("email", email);
    url.searchParams.set(
      "error",
      encodeURIComponent("Service indisponible, réessayez plus tard.")
    );
    return NextResponse.redirect(url);
  }
}
