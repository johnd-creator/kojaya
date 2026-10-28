import assert from "node:assert/strict";
import fs from "node:fs/promises";
import path from "node:path";
import test from "node:test";
import { chromium, expect } from "@playwright/test";

// Serve the actual production bundle with synthetic Inertia props. No Laravel
// server, authentication fixtures, or database writes are involved.
test("compact dues workspace: tooltips, filters and batch review across viewports", async () => {
  const root = path.resolve("public/build");
  const manifest = JSON.parse(
    await fs.readFile(path.join(root, "manifest.json"), "utf8"),
  );
  const entry = manifest["resources/js/app.ts"];
  const browser = await chromium.launch({ channel: "chrome", headless: true });
  try {
    for (const scenario of [
      { width: 1440, height: 900, role: "Admin Koperasi" },
      { width: 1280, height: 900, role: "Pengurus Koperasi" },
      { width: 768, height: 1024, role: "Admin Koperasi" },
      { width: 390, height: 844, role: "Admin Koperasi", touch: true },
    ]) {
      const context = await browser.newContext({
        viewport: { width: scenario.width, height: scenario.height },
        hasTouch: scenario.touch ?? false,
      });
      const errors = [];
      const filterRequests = [];
      const rows = ["UNPAID", "PARTIAL", "PAID", "VOID"].map((status, i) => ({
        id: i + 1,
        status,
        amount: 100000,
        paid_amount:
          status === "PAID" ? 100000 : status === "PARTIAL" ? 25000 : 0,
        period: "2026-10",
        member: { name: `Anggota Uji ${i + 1}`, member_no: `TEST-${i + 1}` },
        contribution_type: { name: "Simpanan Wajib", category: "MANDATORY" },
      }));
      const state = {
        component: "Cooperative/Dues/Index",
        url: "/cooperative/dues",
        version: null,
        props: {
          auth: {
            user: { id: 99, name: "Admin Uji", email: "admin@example.test" },
            roles: [scenario.role],
            permissions: [
              "manage_cooperative_dues",
              "manage_cooperative_payment",
            ],
          },
          csrf_token: "synthetic-csrf",
          flash: {},
          errors: {},
          sidebarOpen: true,
          bankCollectionWindow: {
            period: "2026-10",
            opens_on: "2026-10-25",
            closes_on: "2026-11-07",
            is_open: true,
          },
          invoices: { data: rows, total: 4, from: 1, to: 4, links: [] },
          filters: { period: "2026-10", period_scope: "period" },
          contributionTypes: [{ id: 1, name: "Simpanan Wajib" }],
          categories: ["MANDATORY"],
          canResetPaidDues: scenario.role === "Pengurus Koperasi",
          monthlyDuesInfo: {
            title: "Simpanan Wajib Oktober 2026",
            period: "2026-10",
            period_label: "Oktober 2026",
            next_period_label: "November 2026",
            amount: 100000,
            due_date: "2026-11-10",
            total_invoices: 4,
          },
          stats: {
            total_invoices: 4,
            total_nominal: 400000,
            total_paid: 125000,
            total_outstanding: 175000,
            paid_count: 1,
          },
        },
      };
      await context.route("**/*", async (route) => {
        const request = route.request();
        const url = new URL(request.url());
        if (url.pathname.startsWith("/build/")) {
          const file = path.resolve("public", url.pathname.slice(1));
          assert.ok(file.startsWith(root + path.sep));
          return route.fulfill({
            body: await fs.readFile(file),
            contentType: file.endsWith(".js")
              ? "text/javascript"
              : file.endsWith(".css")
                ? "text/css"
                : "application/octet-stream",
          });
        }
        if (url.pathname === "/images/logo_kjy2.png") {
          return route.fulfill({
            body: await fs.readFile("public/images/logo_kjy2.png"),
            contentType: "image/png",
          });
        }
        if (url.pathname === "/api/notifications/recent") {
          return route.fulfill({
            json: { data: [], meta: { unread_count: 0 } },
          });
        }
        if (
          url.pathname === "/cooperative/dues" &&
          request.method() === "GET"
        ) {
          if (request.headers()["x-inertia"]) {
            filterRequests.push(Object.fromEntries(url.searchParams));
            state.url = url.pathname + url.search;
            return route.fulfill({
              json: state,
              headers: { "X-Inertia": "true" },
            });
          }
          const encoded = JSON.stringify(state)
            .replaceAll("&", "&amp;")
            .replaceAll('"', "&quot;");
          return route.fulfill({
            body: `<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1">${(entry.css ?? []).map((file) => `<link rel="stylesheet" href="/build/${file}">`).join("")}</head><body><div id="app" data-page="${encoded}"></div><script type="module" src="/build/${entry.file}"></script></body></html>`,
            contentType: "text/html",
          });
        }
        errors.push(`Unexpected request ${request.method()} ${url.pathname}`);
        return route.abort();
      });
      const page = await context.newPage();
      page.on("pageerror", (error) => errors.push(error.message));
      page.on("console", (message) => {
        if (message.type() === "error") errors.push(message.text());
      });
      await page.goto("http://dues.test/cooperative/dues");
      const summary = page.getByRole("region", {
        name: "Ringkasan iuran",
        exact: true,
      });
      await expect(summary.locator("dd")).toHaveCount(8);
      const filterButton = page.getByRole("button", {
        name: /^Filter Tagihan/,
      });
      const batchButton = page.getByRole("button", { name: /^Batch Payment/ });
      await expect(
        page.getByRole("region", { name: "Pilihan filter tagihan" }),
      ).toBeHidden();
      await expect(
        page.getByRole("region", { name: "Pengaturan batch payment" }),
      ).toBeHidden();
      if (scenario.width >= 1280) {
        const tops = await summary
          .locator("dl > div")
          .evaluateAll((items) =>
            items.map((item) => Math.round(item.getBoundingClientRect().top)),
          );
        assert.equal(new Set(tops).size, 1, "all eight metrics share a row");
        const filterBox = await filterButton.boundingBox();
        const batchBox = await batchButton.boundingBox();
        assert.equal(
          Math.round(filterBox.y),
          Math.round(batchBox.y),
          "filter and batch share a row",
        );
      }
      await fs.mkdir("test-results", { recursive: true });
      await page.screenshot({
        path: `test-results/dues-compact-${scenario.width}.png`,
        fullPage: true,
      });
      const info = page.getByRole("button", {
        name: "Informasi iuran dan autodebit",
      });
      if (scenario.touch) await info.tap();
      else await info.hover();
      await expect(page.locator('[data-slot="tooltip-content"]')).toContainText(
        "2026-10-25 s/d 2026-11-07",
      );
      await page.keyboard.press("Escape");
      await expect(page.locator('[data-slot="tooltip-content"]')).toBeHidden();
      await page.mouse.move(0, 0);
      await filterButton.focus();
      await page.keyboard.press("Shift+Tab");
      await info.focus();
      await expect(page.locator('[data-slot="tooltip-content"]')).toBeVisible();
      await page.keyboard.press("Escape");

      await filterButton.click();
      await expect(filterButton).toHaveAttribute("aria-expanded", "true");
      await page.getByRole("combobox", { name: "Status tagihan" }).click();
      await page
        .getByRole("option", { name: "Belum lunas", exact: true })
        .click();
      await page.getByRole("button", { name: "Terapkan", exact: true }).click();
      await expect.poll(() => filterRequests.length).toBe(1);
      assert.equal(filterRequests[0].status, "OPEN");
      assert.equal(filterRequests[0].period, "2026-10");
      await filterButton.click();
      await expect(
        page.getByRole("region", { name: "Pilihan filter tagihan" }),
      ).toBeHidden();

      await batchButton.click();
      const process = page.getByRole("button", { name: /^Proses/ });
      await expect(process).toBeDisabled();
      await page
        .getByRole("checkbox", {
          name: "Pilih semua tagihan yang dapat dibayar",
        })
        .check();
      await expect(process).toHaveText("Proses (2)");
      await expect(process).toBeEnabled();
      await page
        .getByLabel("Tanggal bayar", { exact: true })
        .fill("2026-10-28");
      await page.getByRole("combobox", { name: "Metode pembayaran" }).click();
      await page
        .getByRole("option", { name: "Transfer Bank", exact: true })
        .click();
      await page.getByLabel("No. referensi", { exact: true }).fill("TEST-REF");
      await batchButton.click();
      await batchButton.click();
      await expect(
        page.getByLabel("No. referensi", { exact: true }),
      ).toHaveValue("TEST-REF");
      await process.click();
      const dialog = page.getByRole("dialog");
      await expect(dialog).toContainText("Konfirmasi Batch Payment");
      await expect(dialog).toContainText("Rp 175.000");
      await expect(dialog).toContainText("Transfer Bank");
      await dialog.getByRole("button", { name: "Batal", exact: true }).click();
      await page.getByRole("button", { name: "Hapus pilihan tagihan" }).click();
      await expect(process).toBeDisabled();
      await expect(page.getByRole("checkbox").nth(3)).toBeDisabled();
      await expect(page.getByRole("checkbox").nth(4)).toBeDisabled();
      assert.equal(
        await page
          .getByRole("button", { name: "Belum Bayar", exact: true })
          .count(),
        scenario.role === "Pengurus Koperasi" ? 1 : 0,
      );
      await batchButton.click();
      assert.ok(
        await page.evaluate(
          () => document.documentElement.scrollWidth <= innerWidth + 1,
        ),
        "no page-level horizontal overflow",
      );
      assert.deepEqual(errors, []);
      await context.close();
    }
  } finally {
    await browser.close();
  }
});
