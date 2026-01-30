"use client";

import { Container } from "./Container";
import Link from "next/link";
import { ModuleHooks } from "@/components/modules/ModuleHooks";

export function Footer() {
  return (
    <footer className="border-t border-black/5 bg-white py-6 text-xs text-neutral-500">
      <Container>
        {/* Hook: footer.content.extra - Permet d'ajouter du contenu supplémentaire dans le footer */}
        <ModuleHooks
          hookName="footer.content.extra"
          context={{}}
        />

        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <p>© {new Date().getFullYear()} Omersia. Tous droits réservés.</p>
          <div className="flex gap-4">
            <Link href="/legal">Mentions légales</Link>
            <Link href="/cgv">CGV</Link>
            <Link href="/privacy">Confidentialité</Link>
          </div>
        </div>
      </Container>
    </footer>
  );
}
