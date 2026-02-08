// app/auth/password/forgot/route.ts
import { NextRequest, NextResponse } from "next/server";

const BACKEND_URL = process.env.API_INTERNAL_URL;
const FRONTEND_URL = process.env.NEXT_PUBLIC_FRONTEND_URL || "http://localhost:8000";
const API_KEY = process.env.FRONT_API_KEY;

export async function POST(req: NextRequest) {
  const formData = await req.formData();
  const email = String(formData.get("email") || "");

  if (!email) {
    const url = new URL("/password/forgot", FRONTEND_URL);
    url.searchParams.set(
      "error",
      encodeURIComponent("Veuillez renseigner votre adresse e-mail.")
    );
    return NextResponse.redirect(url);
  }

  try {
    const res = await fetch(`${BACKEND_URL}/auth/password/forgot`, {
      method: "POST",
      headers: {
        "X-API-KEY": API_KEY || "",
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({ email }),
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      const message =
        data?.message || "Une erreur est survenue lors de l'envoi du lien.";
      const url = new URL("/password/forgot", FRONTEND_URL);
      url.searchParams.set("error", encodeURIComponent(message));
      return NextResponse.redirect(url);
    }

    // Succès : rediriger avec un message de succès
    const url = new URL("/password/forgot", FRONTEND_URL);
    url.searchParams.set(
      "success",
      encodeURIComponent(
        "Un e-mail de réinitialisation a été envoyé si l'adresse existe dans notre système."
      )
    );
    return NextResponse.redirect(url);
  } catch {
    const url = new URL("/password/forgot", FRONTEND_URL);
    url.searchParams.set(
      "error",
      encodeURIComponent("Service indisponible, réessayez plus tard.")
    );
    return NextResponse.redirect(url);
  }
}
