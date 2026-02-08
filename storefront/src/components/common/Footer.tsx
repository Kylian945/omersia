import { Container } from "./Container";
import Link from "next/link";
import { ModuleHooks } from "@/components/modules/ModuleHooks";
import { getMenu } from "@/lib/api-menu";
import { MenuItem } from "@/lib/types/menu-types";

function getItemHref(item: MenuItem): string {
  if (item.url) return item.url;
  if (item.type === "category" && item.category?.slug) {
    return `/categories/${item.category.slug}`;
  }
  if (item.type === "cms_page" && item.cms_page?.slug) {
    return `/content/${item.cms_page.slug}`;
  }
  return "#";
}

export async function Footer() {
  const menu = await getMenu("footer");
  const footerItems: MenuItem[] =
    menu?.items?.filter(
      (item) => item.type !== "text" && (item.url || item.category || item.cms_page)
    ) || [];

  const hasFooterMenu = footerItems.length > 0;
  const currentYear = new Date().getFullYear();

  return (
    <footer className="border-t border-black/5 bg-white py-6 text-xs text-neutral-500">
      <Container>
        {/* Hook: footer.content.extra - Permet d'ajouter du contenu supplémentaire dans le footer */}
        <ModuleHooks
          hookName="footer.content.extra"
          context={{}}
        />

        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <p>© {currentYear} Omersia. Tous droits réservés.</p>
          <div className="flex gap-4">
            {hasFooterMenu && (
              footerItems.map((item) => (
                <Link key={item.id} href={getItemHref(item)}>
                  {item.label}
                </Link>
              ))
            )}
          </div>
        </div>
      </Container>
    </footer>
  );
}
