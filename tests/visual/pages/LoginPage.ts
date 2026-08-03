import { expect, type Page } from "@playwright/test";

export class LoginPage {
    public constructor(private readonly page: Page) {}

    public async login(email: string, password: string, role: string): Promise<void> {
        await this.page.goto("/login");
        await this.page.locator('input[name="email"]').fill(email);
        await this.page.locator('input[name="password"]').fill(password);
        await this.page.locator('[data-test="login-button"]').click();
        await expect(this.page).not.toHaveURL(/\/login(?:\?|$)/);
        await this.page.reload();
        await this.assertRole(role);
    }

    private async assertRole(role: string): Promise<void> {
        const pageData = await this.page.locator("#app").getAttribute("data-page");
        if (!pageData) {
            throw new Error("Inertia page data is unavailable after login.");
        }

        const decoded = pageData
            .replace(/&quot;/g, '"')
            .replace(/&#039;/g, "'")
            .replace(/&amp;/g, "&")
            .replace(/&lt;/g, "<")
            .replace(/&gt;/g, ">");
        const parsed = JSON.parse(decoded) as {
            props?: { auth?: { roles?: Array<{ name?: string } | string> } };
        };
        const roles = (parsed.props?.auth?.roles ?? []).map((item) =>
            typeof item === "string" ? item : item.name,
        );

        expect(roles).toContain(role);
    }
}
