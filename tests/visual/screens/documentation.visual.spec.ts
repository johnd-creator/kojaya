import { test, expect } from "@playwright/test";
import path from "node:path";
import { DocumentationPage } from "../pages/DocumentationPage";

const authState = (role: string): string =>
  path.resolve("tests/visual/.auth", `${role}.json`);

function escapeRegex(str: string): string {
  return str.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

// ============================================================
// ANGGOTA
// ============================================================
test.describe("documentation anggota @visual @accessibility", () => {
  test.use({ storageState: authState("anggota") });

  test("landing shows articles for Anggota", async ({ page }) => {
    await page.goto("/documentation");
    const docs = new DocumentationPage(page);
    await expect(docs.heading).toBeVisible();
    await expect(docs.searchInput).toBeVisible();
    await expect(docs.moduleSelect).toBeVisible();
    await expect(docs.articleCards.first()).toBeVisible();
  });

  test("portal overview article renders body", async ({ page }) => {
    await page.goto("/documentation/anggota-portal-overview");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
  });

  test("loan flow article renders body", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
  });

  test("payment flow article renders body", async ({ page }) => {
    await page.goto("/documentation/anggota-payment-flow");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
  });

  test("print button is present", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const printBtn = page.locator("[data-testid='print-article-button']");
    await expect(printBtn).toBeVisible();
  });

  test("related articles are visible", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
    const related = page.locator("a[href^='/documentation/']");
    await expect(related.first()).toBeVisible();
  });

  test("TOC appears and has links", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
    await expect(docs.tocList).toBeVisible();
    await expect(docs.tocLinks.first()).toBeVisible();
  });

  test("TOC click updates URL hash and navigates", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await expect(docs.tocLinks.first()).toBeVisible();
    const href = (await docs.tocLinks.first().getAttribute("href")) ?? "";
    expect(href).toMatch(/^#[a-z0-9-]+$/);
    await docs.tocLinks.first().click();
    await expect(page).toHaveURL(new RegExp(`${escapeRegex(href)}$`));
    await expect(page.locator(href)).toBeVisible();
  });

  test("duplicate headings produce unique IDs", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const ids = await page.evaluate(() => {
      return Array.from(document.querySelectorAll("article h2, article h3"))
        .map((el) => el.id)
        .filter(Boolean);
    });
    const unique = new Set(ids);
    expect(ids.length).toBe(unique.size);
  });

  test("search surfaces loan articles for pinjaman query", async ({ page }) => {
    await page.goto("/documentation");
    const docs = new DocumentationPage(page);
    await docs.search("pinjaman");
    await expect(docs.articleCards.first()).toBeVisible();
  });

  test("search empty state shows user-friendly message", async ({ page }) => {
    await page.goto("/documentation");
    const docs = new DocumentationPage(page);
    await docs.search("zzzz-tidak-ada-hasil");
    const noResults = page.getByText(/tidak ada (panduan|artikel)/i);
    await expect(noResults).toBeVisible();
  });

  test("screenshot thumbnail is present and loaded", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
    await expect(docs.screenshotButton).toHaveCount(1);
    const img = docs.screenshotButton.locator("img");
    await expect(img).toBeVisible();
    const loaded = await img.evaluate(
      (el: HTMLImageElement) =>
        el.complete && el.naturalWidth > 0 && el.naturalHeight > 0,
    );
    expect(loaded).toBe(true);
  });

  test("screenshot modal opens and has accessible name", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await docs.screenshotButton.click();
    await expect(docs.screenshotDialog).toBeVisible();
    await expect(docs.screenshotDialog).toHaveAttribute("role", "dialog");
    await expect(docs.screenshotDialog).toHaveAttribute("aria-modal", "true");
    await expect(docs.screenshotDialog).toHaveAttribute("aria-labelledby");
  });

  test("Escape closes the dialog", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await docs.screenshotButton.click();
    await expect(docs.screenshotDialog).toBeVisible();
    await page.keyboard.press("Escape");
    await expect(docs.screenshotDialog).not.toBeVisible();
  });

  test("focus restores to trigger after close", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await docs.screenshotButton.click();
    await expect(docs.screenshotDialog).toBeVisible();
    await page.keyboard.press("Escape");
    await expect(docs.screenshotDialog).not.toBeVisible();
    await expect(docs.screenshotButton).toBeFocused();
  });

  test("Tab stays within dialog (focus trap)", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await docs.screenshotButton.click();
    await expect(docs.screenshotDialog).toBeVisible();
    for (let i = 0; i < 5; i++) {
      await page.keyboard.press("Tab");
      const inside = await page.evaluate(() => {
        const dialog = document.querySelector("[role='dialog']");
        return Boolean(
          dialog &&
          document.activeElement &&
          dialog.contains(document.activeElement),
        );
      });
      expect(inside).toBe(true);
    }
  });

  test("Shift+Tab stays within dialog", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await docs.screenshotButton.click();
    await expect(docs.screenshotDialog).toBeVisible();
    for (let i = 0; i < 5; i++) {
      await page.keyboard.press("Shift+Tab");
      const inside = await page.evaluate(() => {
        const dialog = document.querySelector("[role='dialog']");
        return Boolean(
          dialog &&
          document.activeElement &&
          dialog.contains(document.activeElement),
        );
      });
      expect(inside).toBe(true);
    }
  });

  test("backdrop click closes dialog", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await docs.screenshotButton.click();
    await expect(docs.screenshotDialog).toBeVisible();
    await docs.screenshotDialog.click({ position: { x: 5, y: 5 } });
    await expect(docs.screenshotDialog).not.toBeVisible();
  });

  test("click on image does not close dialog", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await docs.screenshotButton.click();
    await expect(docs.screenshotDialog).toBeVisible();
    const img = docs.screenshotDialog.locator("img");
    await img.click();
    await expect(docs.screenshotDialog).toBeVisible();
  });
});

// ============================================================
// ANGGOTA — CONTEXTUAL HELP
// ============================================================
test.describe("documentation contextual help @visual", () => {
  test.describe("desktop", () => {
    test.use({
      storageState: authState("anggota"),
      viewport: { width: 1280, height: 720 },
    });

    test("contextual help visible on member portal desktop", async ({
      page,
    }) => {
      await page.goto("/member");
      const helpLink = page.getByRole("link", { name: /lihat panduan/i });
      await expect(helpLink).toHaveCount(1);
      await expect(helpLink).toBeVisible();
      await helpLink.click();
      await expect(page).toHaveURL(/\/documentation\/anggota-portal-overview$/);
    });
  });

  test.describe("mobile", () => {
    test.use({
      storageState: authState("anggota"),
      viewport: { width: 390, height: 844 },
    });

    test("contextual help visible on member portal mobile", async ({
      page,
    }) => {
      await page.goto("/member");
      const helpBtn = page.getByRole("link", { name: /panduan/i });
      await expect(helpBtn).toHaveCount(1);
      await expect(helpBtn).toBeVisible();
      await helpBtn.click();
      await expect(page).toHaveURL(/\/documentation\//);
    });

    test("mobile article has no horizontal overflow", async ({ page }) => {
      await page.goto("/documentation/anggota-loan-flow");
      const docs = new DocumentationPage(page);
      await expect(docs.articleBody).toBeVisible();
      const overflowing = await page.evaluate(() => {
        const el = document.documentElement;
        return el.scrollWidth - el.clientWidth;
      });
      expect(overflowing).toBeLessThanOrEqual(2);
    });
  });
});

// ============================================================
// ADMIN KOPERASI
// ============================================================
test.describe("documentation admin koperasi @visual", () => {
  test.use({ storageState: authState("admin") });

  test("landing shows articles for Admin", async ({ page }) => {
    await page.goto("/documentation");
    const docs = new DocumentationPage(page);
    await expect(docs.heading).toBeVisible();
    await expect(docs.articleCards.first()).toBeVisible();
  });

  test("payment queue article renders body", async ({ page }) => {
    await page.goto("/documentation/admin-koperasi-payment-queue");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
  });

  test("article body is sanitised", async ({ page }) => {
    await page.goto("/documentation/admin-koperasi-payment-queue");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
    const html = await docs.articleBody.innerHTML();
    expect(html).not.toMatch(/<script\b/i);
    expect(html).not.toMatch(/on\w+\s*=/i);
  });

  test("contextual help on payments page", async ({ page }) => {
    await page.goto("/cooperative/payments");
    const helpLink = page.getByRole("link", { name: /lihat panduan/i });
    await expect(helpLink).toHaveCount(1);
    await expect(helpLink).toBeVisible();
    await helpLink.click();
    await expect(page).toHaveURL(
      /\/documentation\/admin-koperasi-payment-queue$/,
    );
  });
});

// ============================================================
// MANAJER KOPERASI
// ============================================================
test.describe("documentation manajer koperasi @visual", () => {
  test.use({ storageState: authState("manajer") });

  test("landing shows articles for Manajer", async ({ page }) => {
    await page.goto("/documentation");
    const docs = new DocumentationPage(page);
    await expect(docs.heading).toBeVisible();
    await expect(docs.articleCards.first()).toBeVisible();
  });

  test("loan review article renders body", async ({ page }) => {
    await page.goto("/documentation/manajer-loan-review");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
  });

  test("contextual help on loans page", async ({ page }) => {
    await page.goto("/cooperative/loans");
    const helpLink = page.getByRole("link", { name: /lihat panduan/i });
    await expect(helpLink).toHaveCount(1);
    await expect(helpLink).toBeVisible();
    await helpLink.click();
    await expect(page).toHaveURL(/\/documentation\/manajer-loan-review$/);
  });
});

// ============================================================
// PENGURUS KOPERASI
// ============================================================
test.describe("documentation pengurus koperasi @visual", () => {
  test.use({ storageState: authState("pengurus") });

  test("landing shows articles for Pengurus", async ({ page }) => {
    await page.goto("/documentation");
    const docs = new DocumentationPage(page);
    await expect(docs.heading).toBeVisible();
    await expect(docs.articleCards.first()).toBeVisible();
  });

  test("loan approval article renders body", async ({ page }) => {
    await page.goto("/documentation/pengurus-loan-approval");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
  });

  test("SHU article renders body", async ({ page }) => {
    await page.goto("/documentation/pengurus-shu-and-governance");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
  });

  test("contextual help on SHU page", async ({ page }) => {
    await page.goto("/cooperative/shu");
    const helpLink = page.getByRole("link", { name: /lihat panduan/i });
    await expect(helpLink).toHaveCount(1);
    await expect(helpLink).toBeVisible();
    await helpLink.click();
    await expect(page).toHaveURL(
      /\/documentation\/pengurus-shu-and-governance$/,
    );
  });
});

// ============================================================
// AUTHORIZATION
// ============================================================
test.describe("documentation authorization @visual", () => {
  test("guest is redirected to login", async ({ request }) => {
    const response = await request.get("/documentation/anggota-loan-flow", {
      maxRedirects: 0,
    });
    expect([302]).toContain(response.status());
  });

  test("admin cannot access pengurus article (403)", async ({ browser }) => {
    const context = await browser.newContext({
      storageState: authState("admin"),
    });
    const page = await context.newPage();
    const response = await page.goto(
      "/documentation/pengurus-shu-and-governance",
      { waitUntil: "commit" },
    );
    expect(response?.status()).toBe(403);
    await context.close();
  });

  test("admin cannot access manajer article (403)", async ({ browser }) => {
    const context = await browser.newContext({
      storageState: authState("admin"),
    });
    const page = await context.newPage();
    const response = await page.goto("/documentation/manajer-loan-review", {
      waitUntil: "commit",
    });
    expect(response?.status()).toBe(403);
    await context.close();
  });

  test("anggota cannot access admin article (403)", async ({ browser }) => {
    const context = await browser.newContext({
      storageState: authState("anggota"),
    });
    const page = await context.newPage();
    const response = await page.goto(
      "/documentation/admin-koperasi-payment-queue",
      { waitUntil: "commit" },
    );
    expect(response?.status()).toBe(403);
    await context.close();
  });

  test("manajer cannot access pengurus article (403)", async ({ browser }) => {
    const context = await browser.newContext({
      storageState: authState("manajer"),
    });
    const page = await context.newPage();
    const response = await page.goto(
      "/documentation/pengurus-shu-and-governance",
      { waitUntil: "commit" },
    );
    expect(response?.status()).toBe(403);
    await context.close();
  });

  test("pengurus cannot access anggota article (403)", async ({ browser }) => {
    const context = await browser.newContext({
      storageState: authState("pengurus"),
    });
    const page = await context.newPage();
    const response = await page.goto("/documentation/anggota-loan-flow", {
      waitUntil: "commit",
    });
    expect(response?.status()).toBe(403);
    await context.close();
  });
});
