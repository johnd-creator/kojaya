import { expect, test } from "@playwright/test";
import { auditAccessibility } from "../helpers/accessibility";
import {
  attachRuntimeHealth,
  writeRuntimeReport,
} from "../helpers/runtime-health";
import {
  installStableEnvironment,
  waitForStableScreen,
} from "../helpers/stable-screen";

test.use({ storageState: "tests/visual/.auth/admin.json" });

const scenarios = [
  ["admin-dashboard", "/dashboard"],
  ["admin-members", "/cooperative/members"],
  ["admin-payments", "/cooperative/payments"],
  ["admin-dues", "/cooperative/dues?period_scope=all&status=OPEN"],
] as const;

for (const [id, route] of scenarios) {
  test(id + " @accessibility", async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== "desktop");
    const runtime = attachRuntimeHealth(page, id);

    try {
      await installStableEnvironment(page);
      const response = await page.goto(route, {
        waitUntil: "domcontentloaded",
      });
      expect(response?.status(), id + " did not return an HTML page.").toBe(
        200,
      );
      await waitForStableScreen(page, { screenId: id });
      await auditAccessibility(page, testInfo, id);
    } finally {
      await writeRuntimeReport(runtime, testInfo);
    }
  });
}

test("admin-payments-mobile @accessibility", async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== "mobile");
  const runtime = attachRuntimeHealth(page, "admin-payments-mobile");

  try {
    await installStableEnvironment(page);
    const response = await page.goto("/cooperative/payments", {
      waitUntil: "domcontentloaded",
    });
    expect(
      response?.status(),
      "admin-payments-mobile did not return an HTML page.",
    ).toBe(200);
    await waitForStableScreen(page, { screenId: "admin-payments-mobile" });
    await auditAccessibility(page, testInfo, "admin-payments-mobile");
  } finally {
    await writeRuntimeReport(runtime, testInfo);
  }
});

test("admin-loan-types-create-dialog @accessibility", async ({
  page,
}, testInfo) => {
  test.skip(testInfo.project.name !== "desktop");
  const runtime = attachRuntimeHealth(page, "admin-loan-types-create-dialog");

  try {
    await installStableEnvironment(page);
    const response = await page.goto("/cooperative/loan-types", {
      waitUntil: "domcontentloaded",
    });
    expect(response?.status()).toBe(200);
    await waitForStableScreen(page, {
      screenId: "admin-loan-types-index-default",
    });
    await page.getByRole("button", { name: "Tambah Tipe Pinjaman" }).click();
    const dialog = page.getByRole("dialog");
    const describedBy = await dialog.getAttribute("aria-describedby");
    expect(describedBy).toBeTruthy();
    await expect(page.locator(`#${describedBy}`)).toBeVisible();
    await auditAccessibility(page, testInfo, "admin-loan-types-create-dialog");
  } finally {
    await writeRuntimeReport(runtime, testInfo);
  }
});

test("admin-loan-types-edit-dialog @accessibility", async ({
  page,
}, testInfo) => {
  test.skip(testInfo.project.name !== "desktop");
  const runtime = attachRuntimeHealth(page, "admin-loan-types-edit-dialog");

  try {
    await installStableEnvironment(page);
    const response = await page.goto("/cooperative/loan-types", {
      waitUntil: "domcontentloaded",
    });
    expect(response?.status()).toBe(200);
    await waitForStableScreen(page, {
      screenId: "admin-loan-types-index-default",
    });
    await page.getByRole("button", { name: "Edit" }).first().click();
    const dialog = page.getByRole("dialog");
    const describedBy = await dialog.getAttribute("aria-describedby");
    expect(describedBy).toBeTruthy();
    await expect(page.locator(`#${describedBy}`)).toBeVisible();
    await auditAccessibility(page, testInfo, "admin-loan-types-edit-dialog");
  } finally {
    await writeRuntimeReport(runtime, testInfo);
  }
});

test("admin-loan-types-delete-confirmation @accessibility", async ({
  page,
}, testInfo) => {
  test.skip(testInfo.project.name !== "desktop");
  const runtime = attachRuntimeHealth(
    page,
    "admin-loan-types-delete-confirmation",
  );

  try {
    await installStableEnvironment(page);
    const response = await page.goto("/cooperative/loan-types", {
      waitUntil: "domcontentloaded",
    });
    expect(response?.status()).toBe(200);
    await waitForStableScreen(page, {
      screenId: "admin-loan-types-index-default",
    });
    await page.getByRole("button", { name: "Hapus" }).first().click();
    const dialog = page.getByRole("dialog");
    const describedBy = await dialog.getAttribute("aria-describedby");
    expect(describedBy).toBeTruthy();
    await expect(page.locator(`#${describedBy}`)).toBeVisible();
    await auditAccessibility(
      page,
      testInfo,
      "admin-loan-types-delete-confirmation",
    );
    await page.getByRole("button", { name: "Batal" }).click();
  } finally {
    await writeRuntimeReport(runtime, testInfo);
  }
});
