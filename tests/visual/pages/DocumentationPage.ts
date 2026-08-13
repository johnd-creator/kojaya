import { type Locator, type Page, expect } from "@playwright/test";

export class DocumentationPage {
  public readonly heading: Locator;
  public readonly searchInput: Locator;
  public readonly categoryFilter: Locator;
  public readonly roleFilter: Locator;
  public readonly guideGrid: Locator;
  public readonly articleCards: Locator;
  public readonly articleSections: Locator;
  public readonly articleCtas: Locator;
  public readonly articleBody: Locator;
  public readonly roleSummary: Locator;
  public readonly quickStart: Locator;
  public readonly quickStartItems: Locator;
  public readonly emptyState: Locator;
  public readonly resetFilters: Locator;
  public readonly referenceSection: Locator;
  public readonly sidebarFooter: Locator;
  public readonly sidebarHelpLink: Locator;
  public readonly screenshotButtons: Locator;
  public readonly screenshotDialog: Locator;
  public readonly contextualHelpLink: Locator;
  public readonly tocList: Locator;
  public readonly tocLinks: Locator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole("heading", { level: 1 });
    this.searchInput = page.getByRole("searchbox", {
      name: /cari panduan/i,
    });
    this.categoryFilter = page.getByTestId("documentation-category-filter");
    this.roleFilter = page.getByTestId("documentation-role-filter");
    this.guideGrid = page.getByTestId("documentation-guide-grid");
    this.articleCards = page.getByTestId("documentation-article-card");
    this.articleSections = page.getByTestId("documentation-article-sections");
    this.articleCtas = page.getByTestId("documentation-article-cta");
    this.articleBody = page.locator("article.prose");
    this.roleSummary = page.getByTestId("documentation-role-summary");
    this.quickStart = page.getByTestId("documentation-quick-start");
    this.quickStartItems = page.getByTestId("documentation-quick-start-item");
    this.emptyState = page.getByTestId("documentation-empty-state");
    this.resetFilters = page.getByTestId("documentation-reset-filters");
    this.referenceSection = page.getByTestId("documentation-reference-section");
    this.sidebarFooter = page.getByTestId("sidebar-footer-navigation");
    this.sidebarHelpLink = page.getByTestId("sidebar-footer-help-link");
    this.screenshotButtons = page.locator("button[aria-label*=screenshot]");
    this.screenshotDialog = page.locator("[role='dialog']");
    this.contextualHelpLink = page.getByRole("link", {
      name: /lihat panduan/i,
    });
    this.tocList = page.getByTestId("documentation-toc");
    this.tocLinks = this.tocList.locator("a[href^='#']");
  }

  async gotoLanding(): Promise<void> {
    await this.page.goto("/documentation");
    await expect(this.heading).toBeVisible();
  }

  async search(query: string): Promise<void> {
    await this.searchInput.fill(query);
  }

  async selectCategory(label: string): Promise<void> {
    await this.categoryFilter
      .getByRole("button", { name: new RegExp(label, "i") })
      .click();
  }

  async selectRole(label: string): Promise<void> {
    await this.roleFilter.selectOption({ label });
  }

  async openArticle(slug: string): Promise<void> {
    await this.page.goto(`/documentation/${slug}`);
    await expect(this.articleBody).toBeVisible();
  }

  screenshotTrigger(id: string): Locator {
    return this.page.getByTestId(`documentation-screenshot-${id}`);
  }
}
