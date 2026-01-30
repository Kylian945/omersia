import { NextRequest, NextResponse } from "next/server";

const BACKEND_URL = process.env.API_INTERNAL_URL;
const FRONTEND_URL = process.env.NEXT_PUBLIC_FRONTEND_URL || "http://localhost:8000";
const API_KEY = process.env.FRONT_API_KEY;

export async function POST(req: NextRequest) {
    const token = req.cookies.get("auth_token")?.value;

    const response = NextResponse.redirect(new URL("/", FRONTEND_URL));
    // Clear cookie Next
    response.cookies.set("auth_token", "", {
        path: "/",
        maxAge: 0,
    });

    if (token) {
        try {
            await fetch(`${BACKEND_URL}/auth/logout`, {
                method: "POST",
                headers: {
                    "X-API-KEY": API_KEY || "",
                    Accept: "application/json",
                    Authorization: `Bearer ${token}`,
                },
            });

            //window.dispatchEvent(new Event("auth:changed"));
        } catch {
            // on ignore les erreurs backend ici
        }
    }

    return response;
}
