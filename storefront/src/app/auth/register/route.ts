// app/auth/register/route.ts
import { NextRequest, NextResponse } from "next/server";

const BACKEND_URL = process.env.API_INTERNAL_URL;
const FRONTEND_URL = process.env.NEXT_PUBLIC_FRONTEND_URL || "http://localhost:8000";
const API_KEY = process.env.FRONT_API_KEY


export async function POST(req: NextRequest) {
  const formData = await req.formData();

  const firstname = String(formData.get("firstname") || "");
  const lastname = String(formData.get("lastname") || "");
  const email = String(formData.get("email") || "");
  const password = String(formData.get("password") || "");
  const password_confirmation = String(
    formData.get("password_confirmation") || ""
  );
  const newsletter = !!formData.get("newsletter");

  // Détecter si c'est un appel AJAX (depuis le checkout)
  const isAjax = req.headers.get("accept")?.includes("application/json");

  if (!email || !password || !password_confirmation) {
    const errorMessage =
      "Veuillez remplir tous les champs obligatoires et accepter les conditions.";

    if (isAjax) {
      return NextResponse.json(
        { success: false, message: errorMessage },
        { status: 400 }
      );
    }

    const url = new URL("/register", FRONTEND_URL);
    url.searchParams.set("error", encodeURIComponent(errorMessage));
    return NextResponse.redirect(url);
  }

  if (password !== password_confirmation) {
    const errorMessage = "Les mots de passe ne correspondent pas.";

    if (isAjax) {
      return NextResponse.json(
        { success: false, message: errorMessage },
        { status: 400 }
      );
    }

    const url = new URL("/register", FRONTEND_URL);
    url.searchParams.set("error", encodeURIComponent(errorMessage));
    return NextResponse.redirect(url);
  }

  try {
    const res = await fetch(`${BACKEND_URL}/auth/register`, {
      method: "POST",
      headers: {
        "X-API-KEY": API_KEY || "",
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({
        firstname: firstname || undefined,
        lastname: lastname || undefined,
        email,
        password,
        password_confirmation,
        newsletter: newsletter ? 1 : 0,
      }),
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      const message =
        data?.message ||
        "Impossible de créer votre compte. Merci de vérifier les informations fournies.";

      if (isAjax) {
        return NextResponse.json(
          { success: false, message },
          { status: res.status }
        );
      }

      const url = new URL("/register", FRONTEND_URL);
      url.searchParams.set("error", encodeURIComponent(message));
      return NextResponse.redirect(url);
    }

    // Créer la réponse appropriée selon le type de requête
    // Rediriger vers l'accueil (l'utilisateur est automatiquement connecté via le token)
    const response = isAjax
      ? NextResponse.json({ success: true, message: "Compte créé avec succès" })
      : NextResponse.redirect(new URL("/", FRONTEND_URL));

    // Définir le cookie auth_token si l'API renvoie un token
    if (data.token) {
      response.cookies.set("auth_token", data.token, {
        httpOnly: true,
        secure: process.env.NODE_ENV === "production",
        sameSite: "lax",
        path: "/",
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

    const url = new URL("/register", FRONTEND_URL);
    url.searchParams.set("error", encodeURIComponent(errorMessage));
    return NextResponse.redirect(url);
  }
}
