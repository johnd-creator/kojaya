import { expect, type Page } from "@playwright/test";

export class StoreCreditPage {
    public constructor(private readonly page: Page) {}

    public async gotoIndex(query = ""): Promise<void> {
        await this.page.goto(`/cooperative/store-credit${query}`);
        await expect(this.page.getByRole("heading", { name: "Saldo Toko Anggota" })).toBeVisible();
    }

    public async openAccountDialog(): Promise<void> {
        await this.page.getByRole("button", { name: "Buka Akun" }).click();
        await expect(this.page.getByRole("dialog")).toBeVisible();
    }

    public async openDetailFor(memberName: string): Promise<void> {
        const row = this.page.getByRole("row").filter({ hasText: memberName });
        await expect(row).toBeVisible();
        const navigation = this.page.waitForURL(
            (url) => /\/cooperative\/store-credit\/[^/]+$/.test(url.pathname),
            { waitUntil: "domcontentloaded" },
        );
        await row.getByRole("link", { name: /detail/i }).click();
        await navigation;
        await expect(this.page.getByRole("heading", { name: memberName })).toBeVisible();
    }

    public async gotoReport(query = ""): Promise<void> {
        await this.page.goto(`/cooperative/store-credit-report${query}`);
        await expect(this.page.getByRole("heading", { name: "Laporan Saldo Toko Anggota" })).toBeVisible();
    }

    public async gotoTransfers(query = ""): Promise<void> {
        await this.page.goto(`/cooperative/store-credit-transfers${query}`);
        await expect(this.page.getByRole("heading", { name: "Verifikasi Setoran Transfer" })).toBeVisible();
    }
}
