import { expect, type Page } from "@playwright/test";

export class LoginPage {
    public constructor(private readonly page: Page) {}

    public async login(email: string, password: string, role: string): Promise<void> {
        await this.page.goto("/login");
        await this.page.locator('input[name="email"]').fill(email);
        await this.page.locator('input[name="password"]').fill(password);
        await this.page.locator('[data-test="login-button"]').click();
        await expect(this.page).not.toHaveURL(/\/login(?:\?|$)/);
        await expect.poll(() => this.readRoles(), { timeout: 15_000 }).toContain(role);
    }

    private async readRoles(): Promise<string[]> {
        const pageData = await this.page.locator("#app").getAttribute("data-page");
        if (!pageData) {
            return [];
        }

        const decoded = pageData
            .replace(/&quot;/g, '"')
            .replace(/&#039;/g, "'")
            .replace(/&amp;/g, "&")
            .replace(/&lt;/g, "<")
            .replace(/&gt;/g, ">");
        const parsed = JSON.parse(decoded) as {
            props?: {
                auth?: {
                    roles?: Array<{ name?: string } | string>;
                    user?: { roles?: Array<{ name?: string } | string> };
                };
            };
        };
        const roleValues = parsed.props?.auth?.roles?.length
            ? parsed.props.auth.roles
            : (parsed.props?.auth?.user?.roles ?? []);
        const roles = roleValues.map((item) =>
            typeof item === "string" ? item : item.name,
        );

        return roles.filter((role): role is string => typeof role === "string");
    }
}
