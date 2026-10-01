import { expect, test } from "@playwright/test";
import { LoginPage } from "../pages/LoginPage";
import { installStableEnvironment } from "../helpers/stable-screen";
import path from "node:path";

test.describe("authentication Inertia navigation", () => {
  // Logout destroys the server-side session, not just this browser's cookies.
  // Never log out the serialized session reused by the other audit suites.
  test.use({ storageState: { cookies: [], origins: [] } });

  test.beforeEach(async ({ page }) => {
    await installStableEnvironment(page);
    await new LoginPage(page).login(
      "ui.pengurus@kojaya.test",
      "UiAudit!2026",
      "pengurus",
    );
  });

  test.afterEach(async ({ browser, baseURL }) => {
    const sharedContext = await browser.newContext({
      baseURL,
      storageState: path.resolve("tests/visual/.auth/pengurus.json"),
    });
    try {
      const sharedPage = await sharedContext.newPage();
      const response = await sharedPage.goto("/dashboard");
      expect(response?.status()).toBe(200);
      await expect(sharedPage).toHaveURL(/\/dashboard(?:\?|$)/);
      await expect(sharedPage.locator("#main-content")).toBeVisible();
    } finally {
      await sharedContext.close();
    }
  });

  test("header logout reaches the login page without a reload", async ({
    page,
  }) => {
    await page.goto("/dashboard");
    await page.getByRole("button", { name: "Keluar" }).click();

    await expect(page).toHaveURL(/\/login(?:\?|$)/);
    await expect(page.locator('input[name="email"]')).toBeVisible();
  });

  test("user menu logout reaches the login page without a reload", async ({
    page,
  }) => {
    await page.goto("/dashboard");
    if ((page.viewportSize()?.width ?? 1440) <= 768) {
      await page.getByRole("button", { name: "Toggle Sidebar" }).click();
    }
    await page.locator('[data-test="sidebar-menu-button"]').click();
    await page.locator('[data-test="logout-button"]').click();

    await expect(page).toHaveURL(/\/login(?:\?|$)/);
    await expect(page.locator('input[name="email"]')).toBeVisible();
  });
});
