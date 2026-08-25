import { expect, type Page } from "@playwright/test";

export class LoginPage {
    public constructor(private readonly page: Page) {}

    public async login(email: string, password: string, role: string): Promise<void> {
        await this.page.goto("/login");
        await this.page.locator('input[name="email"]').fill(email);
        await this.page.locator('input[name="password"]').fill(password);
        await this.page.locator('[data-test="login-button"]').click();
        await expect(this.page).not.toHaveURL(/\/login(?:\?|$)/);
        await expect(
            this.page.locator("#main-content"),
            `Expected the ${role} dashboard to render after login.`,
        ).toBeVisible();
    }
}
