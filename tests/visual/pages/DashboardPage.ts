import { expect, type Page } from "@playwright/test";

export class DashboardPage {
    public constructor(private readonly page: Page) {}

    public async goto(): Promise<void> {
        await this.page.goto("/dashboard");
        await expect(this.page.getByRole("heading").first()).toBeVisible();
    }
}
