import { expect, type Page } from "@playwright/test";
import path from "node:path";
import { LoginPage } from "../pages/LoginPage";
import { installStableEnvironment } from "../helpers/stable-screen";

const password = "UiAudit!2026";

export async function createRoleStorageState(
    page: Page,
    role: string,
    email: string,
    fileName: string,
): Promise<void> {
    await installStableEnvironment(page);
    await new LoginPage(page).login(email, password, role);
    await expect(page).not.toHaveURL(/\/login(?:\?|$)/);
    await page.context().storageState({
        path: path.resolve("tests/visual/.auth", `${fileName}.json`),
    });
}
