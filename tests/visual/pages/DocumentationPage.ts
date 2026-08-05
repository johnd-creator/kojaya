import { type Locator, type Page, expect } from "@playwright/test";

/**
 * Page object for the in-app documentation center
 * (`/documentation`). The object is used by the visual specs that
 * cover landing, article, search, and contextual-help scenarios.
 */
export class DocumentationPage {
  public readonly heading: Locator;
  public readonly searchInput: Locator;
  public readonly moduleSelect: Locator;
  public readonly articleCards: Locator;
  public readonly articleBody: Locator;
  public readonly screenshotButton: Locator;
  public readonly contextualHelpLink: Locator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole("heading", { level: 1 });
    this.searchInput = page.getByRole("searchbox", {
      name: /cari artikel/i,
    });
    this.moduleSelect = page.getByRole("combobox", { name: /modul/i });
    this.articleCards = page.locator("a[href^='/documentation/']");
    this.articleBody = page.locator("article.prose");
    this.screenshotButton = page.locator("button[aria-label*=screenshot]");
    this.contextualHelpLink = page.getByRole("link", {
      name: /lihat panduan/i,
    });
  }

  async gotoLanding(): Promise<void> {
    await this.page.goto("/documentation");
    await expect(this.heading).toBeVisible();
  }

  async search(query: string): Promise<void> {
    await this.searchInput.fill(query);
  }

  async selectModule(value: string): Promise<void> {
    await this.moduleSelect.selectOption(value);
  }

  async openArticle(slug: string): Promise<void> {
    await this.page.goto(`/documentation/${slug}`);
    await expect(this.articleBody).toBeVisible();
  }

  async openScreenshot(): Promise<void> {
    if (await this.screenshotButton.count()) {
      await this.screenshotButton.first().click();
    }
  }
}
