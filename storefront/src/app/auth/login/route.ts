// app/auth/login/route.ts
import { NextRequest, NextResponse } from "next/server";

const BACKEND_URL = process.env.API_INTERNAL_URL;
const FRONTEND_URL = process.env.NEXT_PUBLIC_FRONTEND_URL || "http://localhost:8000";

const API_KEY = process.env.FRONT_API_KEY;

export async function POST(req: NextRequest) {
  const formData = await req.formData();

  const email = String(formData.get("email") || "");
  const password = String(formData.get("password") || "");
  const remember = formData.get("remember") ? 1 : 0;

  // Détecter si c'est un appel AJAX (depuis le checkout)
  const isAjax = req.headers.get("accept")?.includes("application/json");

  if (!email || !password) {
    const errorMessage = "Veuillez renseigner vos identifiants.";

    if (isAjax) {
      return NextResponse.json(
        { success: false, message: errorMessage },
        { status: 400 }
      );
    }

    const url = new URL("/login", FRONTEND_URL);
    url.searchParams.set("error", encodeURIComponent(errorMessage));
    return NextResponse.redirect(url);
  }

  try {
    const res = await fetch(`${BACKEND_URL}/auth/login`, {
      method: "POST",
      headers: {
        "X-API-KEY": API_KEY || "",
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({ email, password, remember }),
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      const message =
        data?.message || "Identifiants incorrects ou erreur serveur.";

      if (isAjax) {
        return NextResponse.json(
          { success: false, message },
          { status: res.status }
        );
      }

      const url = new URL("/login", FRONTEND_URL);
      url.searchParams.set("error", encodeURIComponent(message));
      return NextResponse.redirect(url);
    }

    // Créer la réponse appropriée selon le type de requête
    const response = isAjax
      ? NextResponse.json({ success: true, message: "Connexion réussie" })
      : NextResponse.redirect(new URL("/", FRONTEND_URL));

    // Définir le cookie auth_token
    if (data.token) {
      response.cookies.set("auth_token", data.token, {
        httpOnly: true,
        secure: process.env.NODE_ENV === "production",
        sameSite: "lax",
        path: "/",
        maxAge: remember ? 60 * 60 * 24 * 30 : 60 * 60 * 4, // 30j vs 4h
      });
    }

    return response;
  } catch (e) {
    const errorMessage = "Service indisponible, réessayez plus tard.";

    if (isAjax) {
      return NextResponse.json(
        { success: false, message: errorMessage },
        { status: 503 }
      );
    }

    const url = new URL("/login", FRONTEND_URL);
    url.searchParams.set("error", encodeURIComponent(errorMessage));
    return NextResponse.redirect(url);
  }
}
