import { expect, test, type Page, type Request } from "@playwright/test";
import { LoginPage } from "../pages/LoginPage";
import { installStableEnvironment } from "../helpers/stable-screen";
import path from "node:path";

async function expectUiLogout(page: Page, activate: () => Promise<void>): Promise<void> {
  const requests: Request[] = [];
  const runtimeErrors: string[] = [];
  page.on("request", (request) => {
    if (request.method() === "POST" && new URL(request.url()).pathname === "/logout") {
      requests.push(request);
    }
  });
  page.on("pageerror", (error) => runtimeErrors.push(error.message));
  page.on("console", (message) => {
    if (message.type() === "error") runtimeErrors.push(message.text());
  });
  await page.evaluate(() => Reflect.set(window, "logoutNavigationSentinel", "inertia"));

  await activate();

  await expect(page).toHaveURL(/\/login(?:\?|$)/);
  await expect(page.locator('input[name="email"]')).toBeVisible();
  expect(requests, "One UI activation must send exactly one logout POST").toHaveLength(1);
  expect(await requests[0].headerValue("x-inertia")).toBe("true");
  expect(await page.evaluate(() => Reflect.get(window, "logoutNavigationSentinel")))
    .toBe("inertia");
  expect(runtimeErrors, "Logout must not produce browser runtime errors").toEqual([]);
}

async function openUserMenu(page: Page): Promise<void> {
  if ((page.viewportSize()?.width ?? 1440) <= 768) {
    await page.getByRole("button", { name: "Toggle Sidebar" }).click();
  }
  await page.locator('[data-test="sidebar-menu-button"]').click();
  await expect(page.getByRole("menuitem", { name: "Log out", exact: true })).toBeVisible();
}

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
    await expectUiLogout(page, () => page.getByRole("button", { name: "Keluar" }).click());
  });

  test("user menu logout reaches the login page without a reload", async ({
    page,
  }) => {
    await page.goto("/dashboard");
    await openUserMenu(page);
    await expectUiLogout(page, () => page.locator('[data-test="logout-button"]').click());
  });

  for (const key of ["Enter", "Space"]) {
    test(`user menu logout supports ${key} without a reload`, async ({ page }) => {
      await page.goto("/dashboard");
      await openUserMenu(page);
      const logoutItem = page.getByRole("menuitem", { name: "Log out", exact: true });
      await logoutItem.focus();
      await expectUiLogout(page, () => logoutItem.press(key));
    });
  }
});
