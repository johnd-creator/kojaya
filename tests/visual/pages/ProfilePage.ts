import { expect, type Page } from "@playwright/test";

export class ProfilePage {
    public constructor(private readonly page: Page) {}

    public async goto(): Promise<void> {
        await this.page.goto("/settings/profile");
        await expect(this.page.getByRole("heading", { name: /profile settings/i })).toBeAttached();
    }
}
