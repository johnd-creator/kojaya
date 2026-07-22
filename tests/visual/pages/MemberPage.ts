import { expect, type Page } from "@playwright/test";

export class MemberPage {
    public constructor(private readonly page: Page) {}

    public async goto(): Promise<void> {
        await this.page.goto("/cooperative/members");
        await expect(this.page.getByRole("heading").first()).toBeVisible();
    }
}
