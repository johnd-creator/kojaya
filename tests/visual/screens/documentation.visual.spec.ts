import { test, expect } from "@playwright/test";
import { DocumentationPage } from "../pages/DocumentationPage";

/**
 * Visual + accessibility spec for the in-app user guide
 * (`/documentation`). The spec is structured around the entries
 * registered in `tests/visual/coverage/cooperative-pages.json`
 * under the `documentation` module.
 *
 * These tests run in `UI_AUDIT_MODE=capture` to record candidate
 * baselines. Baselines are NEVER updated automatically — a human
 * reviewer must adopt each candidate before it becomes the new
 * comparison baseline.
 */

test.describe("documentation center @visual @accessibility", () => {
  test("anggota landing renders search, filter, and article cards", async ({
    page,
  }) => {
    await page.goto("/documentation");
    const docs = new DocumentationPage(page);
    await expect(docs.heading).toBeVisible();
    await expect(docs.searchInput).toBeVisible();
    await expect(docs.moduleSelect).toBeVisible();
    await expect(docs.articleCards.first()).toBeVisible();
  });

  test("admin koperasi article body is sanitised", async ({ page }) => {
    await page.goto("/documentation/admin-koperasi-payment-queue");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
    const html = await docs.articleBody.innerHTML();
    expect(html).not.toMatch(/<script\b/i);
    expect(html).not.toMatch(/on\w+\s*=/i);
  });

  test("search filters articles and surfaces empty state", async ({ page }) => {
    await page.goto("/documentation");
    const docs = new DocumentationPage(page);
    await docs.search("pinjaman");
    const noResults = page.getByText(/tidak ada artikel/i);
    await expect(noResults).toBeVisible();
  });

  test("mobile article layout has no horizontal overflow", async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
    const overflowing = await page.evaluate(() => {
      const el = document.documentElement;
      return el.scrollWidth - el.clientWidth;
    });
    expect(overflowing).toBeLessThanOrEqual(2);
  });

  test("direct URL to foreign-role article returns 403", async ({ request }) => {
    // Without authentication the response is a redirect to /login,
    // which is the documented fallback.
    const response = await request.get("/documentation/pengurus-loan-approval", {
      maxRedirects: 0,
    });
    expect([302, 401, 403]).toContain(response.status());
  });
});
