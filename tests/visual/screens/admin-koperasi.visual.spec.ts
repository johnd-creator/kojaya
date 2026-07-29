import { expect, test, type Page } from "@playwright/test";
import { captureScreen } from "../helpers/audit-manifest";
import {
  attachRuntimeHealth,
  writeRuntimeReport,
} from "../helpers/runtime-health";
import {
  assertNoHorizontalOverflow,
  installStableEnvironment,
} from "../helpers/stable-screen";
import { screen } from "../helpers/screen-registry";

test.use({ storageState: "tests/visual/.auth/admin.json" });

const scenarios = [
  ["admin-dashboard-dashboard-admin-koperasi", "/dashboard"],
  ["admin-members-index-default", "/cooperative/members"],
  [
    "admin-members-index-pending-filter",
    "/cooperative/members?validation_status=PENDING",
  ],
  [
    "admin-members-index-no-results",
    "/cooperative/members?search=UI-NO-RESULT-999",
  ],
  ["admin-payments-index-pending", "/cooperative/payments"],
  ["admin-payments-index-empty", "/cooperative/payments?status=APPROVED"],
  ["admin-dues-index-open", "/cooperative/dues?period_scope=all&status=OPEN"],
  ["admin-dues-index-partial", "/cooperative/dues?status=PARTIAL"],
  [
    "admin-dues-index-no-results",
    "/cooperative/dues?member_search=UI-NO-RESULT-999",
  ],
  ["ledger-index-default", "/cooperative/ledger"],
  ["admin-loans-index-default", "/cooperative/loans"],
  ["admin-loan-types-index-default", "/cooperative/loan-types"],
  ["admin-points-index-default", "/cooperative/points"],
] as const;

const layoutCardSelectors = {
  "ledger-index-default": '[data-testid="ledger-filter-card"]',
  "admin-loans-index-default": '[data-testid="loans-list-card"]',
  "admin-loan-types-index-default": '[data-testid="loan-types-list-card"]',
  "admin-points-index-default": '[data-testid="points-table-card"]',
} as const;

async function assertCooperativeResponsiveLayout(
  page: Page,
  id: string,
): Promise<void> {
  const metrics = await page.evaluate(() => ({
    documentWidth: document.documentElement.scrollWidth,
    viewportWidth: window.innerWidth,
  }));

  expect(
    metrics.documentWidth,
    `${id} overflows the viewport.`,
  ).toBeLessThanOrEqual(metrics.viewportWidth + 1);

  const cardSelector =
    layoutCardSelectors[id as keyof typeof layoutCardSelectors];
  const card = page.locator(cardSelector);
  const cardMetrics = await card.evaluate((element) => {
    const region = element.querySelector<HTMLElement>('[role="region"]');
    const table = region?.querySelector<HTMLTableElement>("table");

    return {
      cardRight: element.getBoundingClientRect().right,
      regionRight: region?.getBoundingClientRect().right ?? 0,
      regionWidth: region?.clientWidth ?? 0,
      tableWidth: table?.scrollWidth ?? 0,
    };
  });

  expect(cardMetrics.cardRight).toBeLessThanOrEqual(metrics.viewportWidth + 1);

  if (cardMetrics.regionWidth > 0) {
    expect(cardMetrics.regionRight).toBeLessThanOrEqual(
      metrics.viewportWidth + 1,
    );
    expect(cardMetrics.tableWidth).toBeGreaterThan(0);
  }

  if (id === "ledger-index-default" && metrics.viewportWidth >= 1024) {
    const filterItems = page.locator('[data-testid="ledger-filter-grid"] > *');
    const tops = await filterItems.evaluateAll((items) =>
      items.map((item) => Math.round(item.getBoundingClientRect().top)),
    );

    expect(new Set(tops).size, "Ledger filters should stay on one row.").toBe(
      1,
    );
  }
}

async function assertPaymentLayout(page: Page): Promise<void> {
  const viewport = page.viewportSize();
  const metrics = await page.evaluate(() => {
    const historyCard = document.querySelector<HTMLElement>(
      '[data-testid="payment-history-card"]',
    );
    const tableRegion =
      historyCard?.querySelector<HTMLElement>('[role="region"]');
    const table = tableRegion?.querySelector<HTMLTableElement>("table");

    return {
      documentWidth: document.documentElement.scrollWidth,
      viewportWidth: window.innerWidth,
      historyRight: historyCard?.getBoundingClientRect().right ?? 0,
      regionRight: tableRegion?.getBoundingClientRect().right ?? 0,
      tableWidth: table?.scrollWidth ?? 0,
      regionWidth: tableRegion?.clientWidth ?? 0,
    };
  });

  expect(metrics.documentWidth).toBeLessThanOrEqual(metrics.viewportWidth + 1);
  expect(metrics.historyRight).toBeLessThanOrEqual(metrics.viewportWidth + 1);
  expect(metrics.regionRight).toBeLessThanOrEqual(metrics.viewportWidth + 1);

  if ((viewport?.width ?? 0) < 640) {
    await expect(
      page.locator('[data-testid="payment-history-card"] table'),
    ).toBeHidden();
    await expect(
      page.locator('[aria-label="Daftar pembayaran dalam tampilan kartu"]'),
    ).toBeVisible();
    const paymentCards = page.locator('[data-testid="payment-card"]');
    if ((await paymentCards.count()) > 0) {
      await expect(
        paymentCards.first().getByText("Keterangan", {
          exact: true,
        }),
      ).toBeVisible();
    }
  } else {
    expect(metrics.tableWidth).toBeLessThanOrEqual(metrics.regionWidth + 1);
    await expect(
      page.locator('[data-testid="payment-history-card"] table'),
    ).toBeVisible();
    await expect(
      page.locator('[data-testid="payment-history-card"] th', {
        hasText: "Keterangan",
      }),
    ).toHaveCount(1);
    await expect(
      page.locator('[aria-label="Daftar pembayaran dalam tampilan kartu"]'),
    ).toBeHidden();
  }
}

for (const [id, route] of scenarios) {
  test(id + " @visual", async ({ page }, testInfo) => {
    const definition = screen(id);
    test.skip(!definition.viewport_policy.includes(testInfo.project.name));
    const runtime = attachRuntimeHealth(page, id);

    try {
      await installStableEnvironment(page);
      const response = await page.goto(route, {
        waitUntil: "domcontentloaded",
      });
      expect(response?.status(), id + " did not return an HTML page.").toBe(
        200,
      );
      if (["tablet", "mobile"].includes(testInfo.project.name)) {
        await assertNoHorizontalOverflow(page);
      }
      if (id in layoutCardSelectors) {
        await assertCooperativeResponsiveLayout(page, id);
      }
      if (id === "admin-loan-types-index-default") {
        await page
          .getByRole("button", { name: "Tambah Tipe Pinjaman" })
          .click();
        await expect(page.getByRole("dialog")).toBeVisible();
        await expect(
          page.getByRole("dialog").getByText("Tambah Tipe Pinjaman", {
            exact: true,
          }),
        ).toBeVisible();
        await page.getByRole("button", { name: "Batal" }).click();
      }
      if (id.startsWith("admin-payments")) {
        await assertPaymentLayout(page);
      }
      await captureScreen(page, testInfo, definition);
    } finally {
      await writeRuntimeReport(runtime, testInfo);
    }
  });
}

const detailScenarios = [
  ["admin-members-show-pending-review", "member-pending-review"],
  ["admin-members-show-revision", "member-revision"],
  ["admin-members-show-active", "member-positive"],
] as const;

for (const [id, fixtureKey] of detailScenarios) {
  test(id + " @visual", async ({ page }, testInfo) => {
    const definition = screen(id);
    test.skip(!definition.viewport_policy.includes(testInfo.project.name));
    const runtime = attachRuntimeHealth(page, id);

    try {
      await installStableEnvironment(page);
      const fixtureResponse = await page.request.get("/__ui-audit/fixtures");
      expect(
        fixtureResponse.ok(),
        "Fixture endpoint failed for " + id + ".",
      ).toBe(true);
      const fixtures = (await fixtureResponse.json()) as Record<
        string,
        string | number
      >;
      const memberId = fixtures[fixtureKey];
      expect(
        memberId,
        "Missing fixture " + fixtureKey + " for " + id + ".",
      ).toBeTruthy();
      const response = await page.goto("/cooperative/members/" + memberId, {
        waitUntil: "domcontentloaded",
      });
      expect(response?.status(), id + " did not return an HTML page.").toBe(
        200,
      );
      if (["tablet", "mobile"].includes(testInfo.project.name)) {
        await assertNoHorizontalOverflow(page);
      }
      await captureScreen(page, testInfo, definition);
    } finally {
      await writeRuntimeReport(runtime, testInfo);
    }
  });
}

test("admin-payments-index-selected @visual", async ({ page }, testInfo) => {
  const id = "admin-payments-index-selected";
  const definition = screen(id);
  test.skip(!definition.viewport_policy.includes(testInfo.project.name));
  const runtime = attachRuntimeHealth(page, id);

  try {
    await installStableEnvironment(page);
    const response = await page.goto("/cooperative/payments", {
      waitUntil: "domcontentloaded",
    });
    expect(response?.status(), id + " did not return an HTML page.").toBe(200);
    await assertPaymentLayout(page);
    const paymentCheckbox =
      testInfo.project.name === "mobile"
        ? page
            .locator('[data-testid="payment-card"] input[type="checkbox"]')
            .first()
        : page.locator("table tbody input[type=checkbox]").first();
    await paymentCheckbox.check();
    await captureScreen(page, testInfo, definition);
  } finally {
    await writeRuntimeReport(runtime, testInfo);
  }
});

test("admin-sidebar-keuangan-active-with-query @sidebar", async ({ page }) => {
  const openSidebarForSmallViewport = async (): Promise<void> => {
    if ((page.viewportSize()?.width ?? 0) <= 768) {
      await page.getByRole("button", { name: "Toggle Sidebar" }).click();
    }
  };

  const keuanganMenu = page
    .locator('[data-sidebar="menu-button"]')
    .filter({ hasText: "Keuangan Anggota" });
  const activeSubmenu = (label: string) =>
    page
      .locator('[data-sidebar="menu-sub-button"][data-active="true"]')
      .filter({ hasText: label });

  await page.goto("/cooperative/payments?status=PENDING", {
    waitUntil: "domcontentloaded",
  });
  await openSidebarForSmallViewport();
  await expect(keuanganMenu).toHaveAttribute("data-state", "open");
  await expect(activeSubmenu("Pembayaran")).toHaveCount(1);

  await page.goto("/cooperative/dues?period_scope=all&status=OPEN", {
    waitUntil: "domcontentloaded",
  });
  await openSidebarForSmallViewport();
  await expect(keuanganMenu).toHaveAttribute("data-state", "open");
  await expect(activeSubmenu("Iuran dan Tagihan")).toHaveCount(1);
});
