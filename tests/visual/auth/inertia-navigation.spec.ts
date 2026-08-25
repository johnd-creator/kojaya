import { expect, test } from "@playwright/test";

test.describe("authentication Inertia navigation", () => {
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
    await page.getByTestId("sidebar-menu-button").click();
    await page.getByTestId("logout-button").click();

    await expect(page).toHaveURL(/\/login(?:\?|$)/);
    await expect(page.locator('input[name="email"]')).toBeVisible();
  });
});
