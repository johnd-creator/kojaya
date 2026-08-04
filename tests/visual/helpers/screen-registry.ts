import fs from "node:fs";
import path from "node:path";
import type { AuditScreenDefinition } from "./audit-manifest";

type RegistryFile = {
    entries: AuditScreenDefinition[];
};

const registryPath = path.resolve("tests/visual/coverage/cooperative-pages.json");
const registry = JSON.parse(fs.readFileSync(registryPath, "utf8")) as RegistryFile;

export const screenRegistry: AuditScreenDefinition[] = registry.entries.map((entry) => ({
    ...entry,
    route: entry.path_template,
}));

export function isAuditScopeIncluded(definition: AuditScreenDefinition): boolean {
    const scope = process.env.UI_AUDIT_SCOPE ?? "all";

    return scope === "all"
        || (scope === "cooperative" && definition.module !== "member-portal")
        || (scope === "member" && definition.module === "member-portal")
        || (scope === "store-credit" && definition.module === "store-credit")
        || (scope === "pos" && ["pos", "pos-inventory"].includes(definition.module));
}

export function screen(id: string): AuditScreenDefinition {
    const definition = screenRegistry.find((item) => item.id === id);

    if (!definition) {
        throw new Error(`Unknown UI audit screen: ${id}`);
    }

    return definition;
}

export function assertUniqueScreenIds(): void {
    const ids = screenRegistry.map((item) => item.id);

    if (new Set(ids).size !== ids.length) {
        throw new Error("UI audit screen registry contains duplicate IDs.");
    }
}

export function assertUniqueAccessibilityOwners(): void {
    const inventoryOwned = new Set(screenRegistry
        .filter((item) => item.accessibility && item.state === "default" && item.viewport_policy.includes("desktop"))
        .map((item) => item.id));
    const accessibilityDirectory = path.resolve("tests/visual/accessibility");
    const duplicateOwners: string[] = [];

    for (const file of fs.readdirSync(accessibilityDirectory)) {
        if (!file.endsWith(".spec.ts") || file === "inventory.accessibility.spec.ts") continue;

        const source = fs.readFileSync(path.join(accessibilityDirectory, file), "utf8");
        for (const id of source.matchAll(/screen\("([^"]+)"\)/g)) {
            if (inventoryOwned.has(id[1])) duplicateOwners.push(`${id[1]} (${file})`);
        }
    }

    if (duplicateOwners.length > 0) {
        throw new Error(`Duplicate accessibility owners: ${duplicateOwners.join(", ")}`);
    }
}
