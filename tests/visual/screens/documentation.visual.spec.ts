import { test, expect } from "@playwright/test";
import path from "node:path";
import { DocumentationPage } from "../pages/DocumentationPage";

const authState = (role: string): string =>
  path.resolve("tests/visual/.auth", `${role}.json`);

test.describe("documentation center @visual @accessibility", () => {
  test("landing renders search, filter, and article cards", async ({
    page,
  }) => {
    await page.goto("/documentation");
    const docs = new DocumentationPage(page);
    await expect(docs.heading).toBeVisible();
    await expect(docs.searchInput).toBeVisible();
    await expect(docs.moduleSelect).toBeVisible();
    await expect(docs.articleCards.first()).toBeVisible();
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

  test("previous and next navigation links work", async ({ page }) => {
    await page.goto("/documentation/anggota-portal-overview");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
    const nextLink = page.getByRole("link", {
      name: /selanjutnya|berikutnya/i,
    });
    if (await nextLink.count()) {
      await nextLink.first().click();
      await expect(docs.articleBody).toBeVisible();
    }
  });

  test("related articles are visible", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
    const related = page.locator("a[href^='/documentation/']");
    await expect(related.first()).toBeVisible();
  });

  test("print button is present on article page", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const printBtn = page.locator("[data-testid='print-article-button']");
    await expect(printBtn).toBeVisible();
  });
});

test.describe("documentation TOC @visual", () => {
  test("table of contents appears on article with headings", async ({
    page,
  }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
    await expect(docs.tocList).toBeVisible();
    await expect(docs.tocLinks.first()).toBeVisible();
  });

  test("clicking TOC link updates URL hash", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await expect(docs.tocLinks.first()).toBeVisible();
    const href = await docs.tocLinks.first().getAttribute("href");
    await docs.tocLinks.first().click();
    expect(page.url()).toContain("#");
    if (href) {
      const hash = href.replace(/.*#/, "");
      const heading = page.locator(`#${hash}`);
      await expect(heading).toBeVisible();
    }
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
});

test.describe("documentation screenshot modal @visual @accessibility", () => {
  test("clicking thumbnail opens dialog", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
    if (await docs.screenshotButton.count()) {
      await docs.screenshotButton.first().click();
      await expect(docs.screenshotDialog).toBeVisible();
    }
  });

  test("dialog has accessible name", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    if (await docs.screenshotButton.count()) {
      await docs.screenshotButton.first().click();
      await expect(docs.screenshotDialog).toHaveAttribute("role", "dialog");
      await expect(docs.screenshotDialog).toHaveAttribute("aria-modal", "true");
    }
  });

  test("Escape closes the dialog", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    if (await docs.screenshotButton.count()) {
      await docs.screenshotButton.first().click();
      await expect(docs.screenshotDialog).toBeVisible();
      await page.keyboard.press("Escape");
      await expect(docs.screenshotDialog).not.toBeVisible();
    }
  });

  test("focus restores to trigger button after close", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    if (await docs.screenshotButton.count()) {
      await docs.screenshotButton.first().click();
      await expect(docs.screenshotDialog).toBeVisible();
      await page.keyboard.press("Escape");
      await expect(docs.screenshotDialog).not.toBeVisible();
      await expect(docs.screenshotButton.first()).toBeFocused();
    }
  });

  test("Tab key stays within dialog (focus trap)", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    if (await docs.screenshotButton.count()) {
      await docs.screenshotButton.first().click();
      await expect(docs.screenshotDialog).toBeVisible();
      const closeBtn = docs.screenshotDialog.locator("button").first();
      await closeBtn.focus();
      await page.keyboard.press("Tab");
      const activeText = await page.evaluate(
        () => document.activeElement?.textContent ?? "",
      );
      const dialogText = (await docs.screenshotDialog.textContent()) ?? "";
      expect(dialogText).toContain(activeText);
    }
  });

  test("backdrop click closes dialog", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    if (await docs.screenshotButton.count()) {
      await docs.screenshotButton.first().click();
      await expect(docs.screenshotDialog).toBeVisible();
      await docs.screenshotDialog.click({ position: { x: 5, y: 5 } });
      await expect(docs.screenshotDialog).not.toBeVisible();
    }
  });
});

test.describe("documentation contextual help @visual", () => {
  test.describe("desktop", () => {
    test.use({
      storageState: authState("anggota"),
      viewport: { width: 1280, height: 720 },
    });

    test("contextual help is visible on member dashboard desktop", async ({
      page,
    }) => {
      await page.goto("/member/dashboard");
      const helpLink = page.getByRole("link", { name: /lihat panduan/i });
      if (await helpLink.count()) {
        await expect(helpLink).toBeVisible();
        await helpLink.click();
        await expect(page).toHaveURL(/\/documentation\//);
      }
    });
  });

  test.describe("mobile", () => {
    test.use({
      storageState: authState("anggota"),
      viewport: { width: 390, height: 844 },
    });

    test("contextual help is visible on member dashboard mobile", async ({
      page,
    }) => {
      await page.goto("/member/dashboard");
      const helpBtn = page.getByRole("link", { name: /panduan/i });
      if (await helpBtn.count()) {
        await expect(helpBtn).toBeVisible();
        const helpText = await helpBtn.first().textContent();
        expect(helpText).toBeTruthy();
      }
    });

    test("mobile article layout has no horizontal overflow", async ({
      page,
    }) => {
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

test.describe("documentation role landings @visual", () => {
  test.describe("as anggota", () => {
    test.use({ storageState: authState("anggota") });

    test("anggota sees role-specific articles", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await expect(docs.heading).toBeVisible();
      await expect(docs.articleCards.first()).toBeVisible();
    });
  });

  test.describe("as admin koperasi", () => {
    test.use({ storageState: authState("admin") });

    test("admin sees role-specific articles", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await expect(docs.heading).toBeVisible();
      await expect(docs.articleCards.first()).toBeVisible();
    });

    test("admin article body is sanitised", async ({ page }) => {
      await page.goto("/documentation/admin-koperasi-payment-queue");
      const docs = new DocumentationPage(page);
      await expect(docs.articleBody).toBeVisible();
      const html = await docs.articleBody.innerHTML();
      expect(html).not.toMatch(/<script\b/i);
      expect(html).not.toMatch(/on\w+\s*=/i);
    });
  });

  test.describe("as manajer koperasi", () => {
    test.use({ storageState: authState("manajer") });

    test("manajer sees role-specific articles", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await expect(docs.heading).toBeVisible();
      await expect(docs.articleCards.first()).toBeVisible();
    });
  });

  test.describe("as pengurus koperasi", () => {
    test.use({ storageState: authState("pengurus") });

    test("pengurus sees role-specific articles", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await expect(docs.heading).toBeVisible();
      await expect(docs.articleCards.first()).toBeVisible();
    });
  });
});

test.describe("documentation authorization @visual", () => {
  test("guest is redirected to login", async ({ request }) => {
    const response = await request.get("/documentation/anggota-loan-flow", {
      maxRedirects: 0,
    });
    expect([302, 401]).toContain(response.status());
  });

  test("foreign-role article returns 403", async ({ browser }) => {
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

  test("admin cannot access manajer article", async ({ browser }) => {
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
});
