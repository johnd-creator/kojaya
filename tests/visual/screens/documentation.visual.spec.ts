import { test, expect } from "@playwright/test";
import path from "node:path";
import { DocumentationPage } from "../pages/DocumentationPage";

const authState = (role: string): string =>
  path.resolve("tests/visual/.auth", `${role}.json`);

function escapeRegex(str: string): string {
  return str.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

const LOAN_DESKTOP_SCREENSHOT_ID = "anggota-loan-flow-desktop";
const LOAN_MOBILE_SCREENSHOT_ID = "anggota-loan-flow-mobile";

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
    await expect(docs.categoryFilter).toBeVisible();
    await expect(docs.roleSummary).toContainText("Anggota");
    await expect(docs.quickStart).toBeVisible();
    await expect(docs.quickStartItems).toHaveCount(3);
    await expect(
      docs.quickStart.getByRole("link", { name: /mengenal portal anggota/i }),
    ).toHaveAttribute("href", "/documentation/anggota-portal-overview");
    await expect(docs.articleSections).toBeVisible();
    await expect(docs.articleCtas.first()).toBeVisible();
    await expect(docs.articleCards.first()).toBeVisible();
  });

  test("sidebar exposes one same-tab footer help link", async ({ page }) => {
    await page.goto("/documentation");
    const docs = new DocumentationPage(page);
    if ((await docs.sidebarFooter.count()) === 0) {
      const sidebarToggle = page.getByRole("button", {
        name: "Toggle Sidebar",
      });
      await expect(sidebarToggle).toBeVisible();
      await sidebarToggle.click();
    }
    await expect(docs.sidebarFooter).toBeVisible();
    await expect(docs.sidebarHelpLink).toHaveCount(1);
    await expect(docs.sidebarHelpLink).not.toHaveAttribute("target");
    await expect(
      docs.sidebarFooter.getByRole("link", {
        name: "Pusat Panduan",
        exact: true,
      }),
    ).toHaveCount(1);

    const pagesBeforeClick = page.context().pages().length;
    await docs.sidebarHelpLink.click();
    await expect(page).toHaveURL(/\/documentation$/);
    expect(page.context().pages()).toHaveLength(pagesBeforeClick);
  });

  test("landing remains within the viewport on desktop", async ({ page }) => {
    await page.goto("/documentation");
    const overflow = await page.evaluate(() => {
      const root = document.documentElement;
      return root.scrollWidth - root.clientWidth;
    });
    expect(overflow).toBeLessThanOrEqual(2);
  });

  test("portal overview article renders body", async ({ page }) => {
    await page.goto("/documentation/anggota-payment-flow");
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

  test("payment flow renders its inline procedure image", async ({ page }) => {
    await page.goto("/documentation/anggota-payment-flow");
    const image = page.getByRole("img", {
      name: "Halaman pembayaran iuran pada portal anggota",
    });
    await expect(image).toBeVisible();
    await expect(image).toHaveAttribute(
      "src",
      "/docs/user-guide/screens/desktop/anggota-payment-flow-desktop.png",
    );

    const loaded = await image.evaluate(
      (element: HTMLImageElement) =>
        element.complete &&
        element.naturalWidth > 0 &&
        element.naturalHeight > 0,
    );
    expect(loaded).toBe(true);

    const overflow = await page.evaluate(() => {
      const root = document.documentElement;
      return root.scrollWidth - root.clientWidth;
    });
    expect(overflow).toBeLessThanOrEqual(2);
  });

  test("print button is present", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const printBtn = page.locator("[data-testid='print-article-button']");
    await expect(printBtn).toBeVisible();
    await page.evaluate(() => {
      window.print = () => {
        document.documentElement.dataset.printCalled = "true";
      };
    });
    await printBtn.click();
    await expect
      .poll(() => page.locator("html").getAttribute("data-print-called"))
      .toBe("true");
  });

  test("related articles are visible", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
    const related = page.getByTestId("documentation-related-articles");
    await expect(related).toBeVisible();
    await expect(
      related.getByRole("link", { name: /mengenal portal anggota/i }),
    ).toHaveAttribute("href", "/documentation/anggota-portal-overview");
  });

  test("previous and next article links navigate to authorized articles", async ({
    page,
  }) => {
    await page.goto("/documentation/anggota-portal-overview");
    const docs = new DocumentationPage(page);
    const navigation = page.getByTestId("documentation-article-navigation");
    const next = navigation.getByRole("link", { name: /berikutnya/i });

    await expect(next).toHaveAttribute(
      "href",
      "/documentation/anggota-loan-flow",
    );
    await expect(next).toBeVisible();
    await next.click();
    await expect(page).toHaveURL(/\/documentation\/anggota-loan-flow$/);
    await expect(docs.articleBody).toBeVisible();
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
    await expect(docs.tocLinks).not.toHaveCount(0);
    await expect(docs.tocLinks.first()).toBeVisible();
    const href = (await docs.tocLinks.first().getAttribute("href")) ?? "";
    expect(href).toMatch(/^#[a-z0-9-]+$/);
    const targetId = href.slice(1);
    await expect(page.locator(`[id="${targetId}"]`)).toHaveCount(1);
    await docs.tocLinks.first().click();
    await expect(page).toHaveURL(new RegExp(`${escapeRegex(href)}$`));
    await expect(page.locator(`[id="${targetId}"]`)).toBeVisible();
  });

  test("duplicate headings produce unique IDs", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const ids = await page.evaluate(() => {
      return Array.from(
        document.querySelectorAll("article h2, article h3"),
      ).map((el) => el.id);
    });
    expect(ids.every(Boolean)).toBe(true);
    const unique = new Set(ids);
    expect(ids.length).toBe(unique.size);
  });

  test("search surfaces loan articles for pinjaman query", async ({ page }) => {
    await page.goto("/documentation");
    const docs = new DocumentationPage(page);
    await docs.search("pinjaman");
    await expect(docs.articleCards.first()).toBeVisible();
  });

  test("category filter and reset action restore the guide list", async ({
    page,
  }) => {
    await page.goto("/documentation");
    const docs = new DocumentationPage(page);
    await docs.selectCategory("Pinjaman");
    await expect(docs.articleCards).toHaveCount(1);
    await expect(docs.articleCards).toContainText(
      /mengajukan dan melacak pinjaman/i,
    );

    await docs.search("kata-yang-tidak-ada");
    await expect(docs.emptyState).toBeVisible();
    await expect(docs.resetFilters).toBeVisible();
    await docs.resetFilters.click();
    await expect(docs.emptyState).not.toBeVisible();
    await expect(docs.articleCards).toHaveCount(4);
  });

  test("search empty state shows user-friendly message", async ({ page }) => {
    await page.goto("/documentation");
    const docs = new DocumentationPage(page);
    await docs.search("zzzz-tidak-ada-hasil");
    await expect(docs.emptyState).toBeVisible();
    await expect(page.getByText(/tidak menemukan panduan/i)).toBeVisible();
    await expect(page.getByText(/reset filter/i)).toBeVisible();
  });

  test("screenshot thumbnail is present and loaded", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await expect(docs.articleBody).toBeVisible();
    await expect(docs.screenshotButtons).toHaveCount(2);

    for (const id of [LOAN_DESKTOP_SCREENSHOT_ID, LOAN_MOBILE_SCREENSHOT_ID]) {
      const trigger = docs.screenshotTrigger(id);
      await expect(trigger).toBeVisible();
      const image = trigger.locator("img");
      await expect(image).toBeVisible();
      const loaded = await image.evaluate(
        (element: HTMLImageElement) =>
          element.complete &&
          element.naturalWidth > 0 &&
          element.naturalHeight > 0,
      );
      expect(loaded).toBe(true);
    }
  });

  test("screenshot modal opens and has accessible name", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    const trigger = docs.screenshotTrigger(LOAN_DESKTOP_SCREENSHOT_ID);
    await trigger.click();
    await expect(docs.screenshotDialog).toBeVisible();
    await expect(docs.screenshotDialog).toHaveAttribute("role", "dialog");
    await expect(docs.screenshotDialog).toHaveAttribute("aria-modal", "true");
    const labelledBy =
      await docs.screenshotDialog.getAttribute("aria-labelledby");
    expect(labelledBy).toBeTruthy();
    await expect(page.locator(`#${labelledBy}`)).toHaveText(/tangkapan layar/i);
    await expect(docs.screenshotDialog).toHaveAccessibleName(
      /tangkapan layar/i,
    );
    await expect(
      docs.screenshotDialog.getByRole("button", { name: /tutup/i }),
    ).toBeFocused();
  });

  test("Escape closes the dialog", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await docs.screenshotTrigger(LOAN_DESKTOP_SCREENSHOT_ID).click();
    await expect(docs.screenshotDialog).toBeVisible();
    await page.keyboard.press("Escape");
    await expect(docs.screenshotDialog).not.toBeVisible();
  });

  test("focus restores to trigger after close", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    const trigger = docs.screenshotTrigger(LOAN_DESKTOP_SCREENSHOT_ID);
    await trigger.click();
    await expect(docs.screenshotDialog).toBeVisible();
    await page.keyboard.press("Escape");
    await expect(docs.screenshotDialog).not.toBeVisible();
    await expect(trigger).toBeFocused();
  });

  test("Tab stays within dialog (focus trap)", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await docs.screenshotTrigger(LOAN_DESKTOP_SCREENSHOT_ID).click();
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
    await docs.screenshotTrigger(LOAN_DESKTOP_SCREENSHOT_ID).click();
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
    await docs.screenshotTrigger(LOAN_DESKTOP_SCREENSHOT_ID).click();
    await expect(docs.screenshotDialog).toBeVisible();
    await docs.screenshotDialog.click({ position: { x: 5, y: 5 } });
    await expect(docs.screenshotDialog).not.toBeVisible();
  });

  test("click on image does not close dialog", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    await docs.screenshotTrigger(LOAN_DESKTOP_SCREENSHOT_ID).click();
    await expect(docs.screenshotDialog).toBeVisible();
    const img = docs.screenshotDialog.locator("img");
    await img.click();
    await expect(docs.screenshotDialog).toBeVisible();
  });

  test("modal locks and restores background scroll", async ({ page }) => {
    await page.goto("/documentation/anggota-loan-flow");
    const docs = new DocumentationPage(page);
    const trigger = docs.screenshotTrigger(LOAN_DESKTOP_SCREENSHOT_ID);
    const originalOverflow = await page
      .locator("body")
      .evaluate((body) => body.style.overflow);

    await trigger.click();
    await expect(page.locator("body")).toHaveCSS("overflow", "hidden");
    await page.keyboard.press("Escape");
    await expect
      .poll(() => page.locator("body").evaluate((body) => body.style.overflow))
      .toBe(originalOverflow);
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

    test("mobile landing stacks help content without overflow", async ({
      page,
    }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await expect(docs.quickStart).toBeVisible();
      await expect(docs.searchInput).toBeVisible();
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
// UX V2 — INFORMATION ARCHITECTURE
// ============================================================
test.describe("documentation UX V2 @visual", () => {
  test.describe("anggota single-role experience", () => {
    test.use({ storageState: authState("anggota") });

    test("hero shows search and context label without role selector", async ({
      page,
    }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await expect(docs.heading).toBeVisible();
      await expect(docs.searchInput).toBeVisible();
      await expect(docs.roleFilter).toHaveCount(0);
      await expect(docs.roleSummary).toContainText("Anggota");
    });

    test("quick start has at most four items", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await expect(docs.quickStart).toBeVisible();
      const count = await docs.quickStartItems.count();
      expect(count).toBeLessThanOrEqual(4);
      expect(count).toBeGreaterThanOrEqual(1);
    });

    test("quick start click navigates to correct article", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await expect(
        docs.quickStart.getByRole("link", {
          name: /mengenal portal anggota/i,
        }),
      ).toHaveAttribute("href", "/documentation/anggota-portal-overview");
    });

    test("glossary appears in references not in workflow grid", async ({
      page,
    }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await expect(docs.referenceSection).toBeVisible();
      await expect(
        docs.referenceSection.getByRole("link", { name: /glosarium/i }),
      ).toBeVisible();
      await expect(
        docs.guideGrid.getByRole("link", { name: /glosarium/i }),
      ).toHaveCount(0);
    });

    test("page headings do not use combined role-category format", async ({
      page,
    }) => {
      await page.goto("/documentation");
      await expect(page.locator("h2").filter({ hasText: /\s·\s/ })).toHaveCount(
        0,
      );
    });

    test("category filter isolates Pinjaman articles", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await docs.selectCategory("Pinjaman");
      await expect(docs.articleCards).toHaveCount(1);
    });

    test("search finds payment-related guides", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await docs.search("pembayaran");
      await expect(docs.articleCards.first()).toBeVisible();
    });
  });

  test.describe("admin koperasi single-role experience", () => {
    test.use({ storageState: authState("admin") });

    test("no role selector with correct context label", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await expect(docs.heading).toBeVisible();
      await expect(docs.roleFilter).toHaveCount(0);
      await expect(docs.roleSummary).toContainText("Admin Koperasi");
    });

    test("cross-role articles do not create fake role context", async ({
      page,
    }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await expect(docs.roleFilter).toHaveCount(0);
      await expect(docs.roleSummary).toContainText("Admin Koperasi");
      await expect(docs.roleSummary.getByText(/manajer/i)).toHaveCount(0);
      await expect(docs.roleSummary.getByText(/pengurus/i)).toHaveCount(0);
    });

    test("quick start shows admin-specific guides", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await expect(docs.quickStart).toBeVisible();
      await expect(docs.quickStartItems.first()).toBeVisible();
      const count = await docs.quickStartItems.count();
      expect(count).toBeLessThanOrEqual(4);
      expect(count).toBeGreaterThanOrEqual(1);
    });
  });

  test.describe("manajer single-role experience", () => {
    test.use({ storageState: authState("manajer") });

    test("no role selector with correct context label", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await expect(docs.heading).toBeVisible();
      await expect(docs.roleFilter).toHaveCount(0);
      await expect(docs.roleSummary).toContainText("Manajer Koperasi");
    });
  });

  test.describe("pengurus single-role experience", () => {
    test.use({ storageState: authState("pengurus") });

    test("no role selector with correct context label", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await expect(docs.heading).toBeVisible();
      await expect(docs.roleFilter).toHaveCount(0);
      await expect(docs.roleSummary).toContainText("Pengurus Koperasi");
    });
  });

  test.describe("system admin multi-role experience", () => {
    test.use({ storageState: authState("system-admin") });

    test("role filter is visible for multi-role user", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await expect(docs.roleFilter).toBeVisible();
    });

    test("selecting admin role isolates admin articles", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await docs.selectRole("Admin Koperasi");
      await expect(
        docs.articleCards.filter({ hasText: /dashboard operasional/i }),
      ).toHaveCount(1);
      await expect(
        docs.articleCards.filter({ hasText: /mengenal portal anggota/i }),
      ).toHaveCount(0);
    });

    test("selecting semua shows articles from multiple roles", async ({
      page,
    }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await docs.selectRole("Semua");
      const count = await docs.articleCards.count();
      expect(count).toBeGreaterThan(3);
    });

    test("quick start respects selected role", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await docs.selectRole("Admin Koperasi");
      await expect(docs.quickStartItems.first()).toBeVisible();
      const count = await docs.quickStartItems.count();
      expect(count).toBeLessThanOrEqual(4);
      expect(count).toBeGreaterThanOrEqual(1);
    });

    test("semua mode shows orientation instead of random quick start", async ({
      page,
    }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await docs.selectRole("Semua");
      await expect(docs.quickStart).toBeVisible();
      await expect(docs.quickStartItems).toHaveCount(0);
    });

    test("selecting anggota shows anggota quick start", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await docs.selectRole("Anggota");
      await expect(docs.quickStartItems.first()).toBeVisible();
      const count = await docs.quickStartItems.count();
      expect(count).toBeLessThanOrEqual(4);
      expect(count).toBeGreaterThanOrEqual(1);
    });

    test("selecting manajer shows manajer quick start", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await docs.selectRole("Manajer Koperasi");
      await expect(docs.quickStartItems.first()).toBeVisible();
      const count = await docs.quickStartItems.count();
      expect(count).toBeLessThanOrEqual(4);
      expect(count).toBeGreaterThanOrEqual(1);
    });

    test("selecting pengurus shows pengurus quick start", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await docs.selectRole("Pengurus Koperasi");
      await expect(docs.quickStartItems.first()).toBeVisible();
      const count = await docs.quickStartItems.count();
      expect(count).toBeLessThanOrEqual(4);
      expect(count).toBeGreaterThanOrEqual(1);
    });

    test("desktop landing has no horizontal overflow", async ({ page }) => {
      await page.goto("/documentation");
      const overflow = await page.evaluate(() => {
        const root = document.documentElement;
        return root.scrollWidth - root.clientWidth;
      });
      expect(overflow).toBeLessThanOrEqual(2);
    });
  });

  test.describe("mobile responsive @accessibility", () => {
    test.use({
      storageState: authState("anggota"),
      viewport: { width: 390, height: 844 },
    });

    test("category chips are usable on mobile", async ({ page }) => {
      await page.goto("/documentation");
      const docs = new DocumentationPage(page);
      await expect(docs.categoryFilter).toBeVisible();
      await docs.selectCategory("Pinjaman");
      await expect(docs.articleCards).toHaveCount(1);
    });

    test("no horizontal overflow on mobile", async ({ page }) => {
      await page.goto("/documentation");
      const overflow = await page.evaluate(() => {
        const el = document.documentElement;
        return el.scrollWidth - el.clientWidth;
      });
      expect(overflow).toBeLessThanOrEqual(2);
    });
  });
});

// ============================================================
// AUTHORIZATION
// ============================================================
test.describe("documentation authorization @visual", () => {
  test("guest is redirected to login", async ({ browser, baseURL }) => {
    const context = await browser.newContext({
      baseURL,
      storageState: { cookies: [], origins: [] },
    });

    try {
      const response = await context.request.get(
        "/documentation/anggota-loan-flow",
        { maxRedirects: 0 },
      );
      expect(response.status()).toBe(302);
      expect(response.headers()["location"]).toMatch(/\/login(?:\?|$)/);
    } finally {
      await context.close();
    }
  });

  test("admin cannot access pengurus article (403)", async ({
    browser,
    baseURL,
  }) => {
    const context = await browser.newContext({
      baseURL,
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

  test("admin cannot access manajer article (403)", async ({
    browser,
    baseURL,
  }) => {
    const context = await browser.newContext({
      baseURL,
      storageState: authState("admin"),
    });
    const page = await context.newPage();
    const response = await page.goto("/documentation/manajer-loan-review", {
      waitUntil: "commit",
    });
    expect(response?.status()).toBe(403);
    await context.close();
  });

  test("anggota cannot access admin article (403)", async ({
    browser,
    baseURL,
  }) => {
    const context = await browser.newContext({
      baseURL,
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

  test("manajer cannot access pengurus article (403)", async ({
    browser,
    baseURL,
  }) => {
    const context = await browser.newContext({
      baseURL,
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

  test("pengurus cannot access anggota article (403)", async ({
    browser,
    baseURL,
  }) => {
    const context = await browser.newContext({
      baseURL,
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
