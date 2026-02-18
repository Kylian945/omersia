import { NextResponse } from "next/server";
import { readFile } from "node:fs/promises";
import path from "node:path";

type ModuleConfig = {
  slug: string;
  name: string;
  version: string;
  hooks?: Array<{
    hookName: string;
    componentPath: string;
    condition?: string;
    priority?: number;
  }>;
};

function sanitizeConfigs(payload: unknown): ModuleConfig[] {
  if (!Array.isArray(payload)) {
    return [];
  }

  return payload.filter(
    (item): item is ModuleConfig =>
      typeof item === "object" &&
      item !== null &&
      typeof (item as ModuleConfig).slug === "string" &&
      typeof (item as ModuleConfig).name === "string" &&
      typeof (item as ModuleConfig).version === "string"
  );
}

export async function GET() {
  const moduleHooksPath = path.join(process.cwd(), "public", "module-hooks.json");

  try {
    const raw = await readFile(moduleHooksPath, "utf8");
    const parsed = JSON.parse(raw);
    const configs = sanitizeConfigs(parsed);

    return NextResponse.json(configs, {
      headers: {
        "Cache-Control": "no-store, max-age=0",
      },
    });
  } catch {
    // No synced modules yet: return a valid empty registry
    return NextResponse.json([], {
      headers: {
        "Cache-Control": "no-store, max-age=0",
      },
    });
  }
}
