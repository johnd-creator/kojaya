import assert from "node:assert/strict";
import fs from "node:fs/promises";
import path from "node:path";
import test from "node:test";
import { chromium } from "@playwright/test";

// Built application, synthetic Inertia props and intercepted HTTP only: no server or database.
test("direct opening balance defaults, preview and stale-input safety at desktop/mobile sizes", async () => {
  const root = path.resolve("public/build");
  const manifest = JSON.parse(
    await fs.readFile(path.join(root, "manifest.json"), "utf8"),
  );
  const entry = manifest["resources/js/app.ts"];
  const url = "http://qar.test/cooperative/members/1/opening-balance";
  const categories = ["POKOK", "WAJIB", "SUKARELA", "KHUSUS"];
  const inertia = {
    component: "Cooperative/Members/OpeningBalance/Wizard",
    url,
    version: null,
    props: {
      auth: {
        user: {
          id: 1,
          name: "Synthetic operator",
          email: "operator@example.test",
        },
        roles: ["Pengurus Koperasi"],
        permissions: ["manage_cooperative_opening_balance"],
      },
      csrf_token: "synthetic-csrf",
      flash: {},
      sidebarOpen: false,
      member: {
        id: 1,
        no_anggota: "TEST-1",
        nama_anggota: "Synthetic member",
        tanggal_aktif: "2016-01-01",
        status: "ACTIVE",
        organization_id: "synthetic",
        organization_name: "Synthetic cooperative",
      },
      contribution_types: categories.map((category, i) => ({
        id: i + 1,
        code: category,
        name: category,
        category,
        default_amount: 50000,
        frequency: "MONTHLY",
      })),
      source_types: {
        MIGRATION_LEDGER: "Migrasi dari buku lama",
        MANUAL_RECONCILIATION: "Rekonsiliasi saldo manual",
        EXCEL_IMPORT: "Import dari Excel",
        BOARD_DECISION: "Keputusan pengurus",
      },
      history: [
        {
          id: 10,
          status: "DRAFT",
          status_label: "Draft",
          status_tone: "amber",
          mode: "DIRECT",
          cut_off_date: "2026-09-30",
          total_amount: 100,
          months_count: 0,
          source_type: "MANUAL_RECONCILIATION",
          lines: [],
        },
        {
          id: 11,
          status: "POSTED",
          status_label: "Posted",
          status_tone: "emerald",
          mode: "CALCULATED",
          total_amount: 600000,
          months_count: 12,
          period_start: "2024-01-01",
          period_end: "2024-12-31",
          source_type: "MIGRATION_LEDGER",
          lines: [],
        },
      ],
      capabilities: { can_post: true, can_void: true },
      default_period: { start: "2016-01-01", end: "2026-09-30" },
    },
  };
  const encoded = JSON.stringify(inertia)
    .replaceAll("&", "&amp;")
    .replaceAll('"', "&quot;");
  const html = `<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1">${(entry.css ?? []).map((file) => `<link rel="stylesheet" href="/build/${file}">`).join("")}</head><body><div id="app" data-page="${encoded}"></div><script type="module" src="/build/${entry.file}"></script></body></html>`;
  const browser = await chromium.launch({ channel: "chrome", headless: true });
  try {
    for (const viewport of [
      { width: 1440, height: 900 },
      { width: 390, height: 844 },
    ]) {
      const page = await browser.newPage({ viewport });
      const errors = [];
      page.on("pageerror", (error) => errors.push(error.message));
      page.on("console", (message) => {
        if (message.type() === "error") errors.push(message.text());
      });
      await page.route("**/*", async (route) => {
        const request = route.request();
        const pathname = new URL(request.url()).pathname;
        if (pathname.startsWith("/build/")) {
          const file = path.resolve("public", pathname.slice(1));
          assert.ok(file.startsWith(root + path.sep));
          const contentType = file.endsWith(".js")
            ? "text/javascript"
            : file.endsWith(".css")
              ? "text/css"
              : "application/octet-stream";
          await route.fulfill({ body: await fs.readFile(file), contentType });
        } else if (pathname === "/images/logo_kjy2.png") {
          await route.fulfill({
            body: await fs.readFile("public/images/logo_kjy2.png"),
            contentType: "image/png",
          });
        } else if (pathname === "/api/notifications/recent") {
          await route.fulfill({
            json: { data: [], meta: { limit: 5, unread_count: 0 } },
          });
        } else if (
          pathname.endsWith("/preview") &&
          request.method() === "POST"
        ) {
          const input = request.postDataJSON();
          assert.equal(input.mode, "DIRECT");
          assert.equal(input.cut_off_date, "2026-09-30");
          assert.deepEqual(
            categories.map((category) =>
              Number(input.direct_amounts[category]),
            ),
            [200000, 125000.5, 30000, 40000],
          );
          await route.fulfill({
            json: {
              preview: {
                calculation_start_period: input.cut_off_date,
                calculation_end_period: input.cut_off_date,
                months_count: 0,
                total_amount: 395000.5,
                has_conflicts: false,
                conflicts: [],
                lines: categories.map((category) => ({
                  category_snapshot: category,
                  total_amount: Number(input.direct_amounts[category]),
                  calculation_method: "DIRECT",
                })),
              },
            },
          });
        } else if (pathname === "/cooperative/members/1/opening-balance") {
          await route.fulfill({ body: html, contentType: "text/html" });
        } else {
          errors.push(`Unexpected request: ${request.method()} ${pathname}`);
          await route.abort();
        }
      });
      await page.goto(url);
      await page.getByLabel("Per tanggal", { exact: true }).waitFor();
      assert.equal(
        await page.getByLabel("Periode Awal Perhitungan").count(),
        0,
      );
      await page.getByText("Riwayat Batch", { exact: true }).waitFor();
      assert.equal(
        await page.getByText("12 bulan", { exact: false }).count(),
        1,
      );
      await page
        .getByRole("button", { name: "Posting ke Ledger", exact: true })
        .click();
      await page
        .getByRole("heading", { name: "Konfirmasi Posting Saldo Awal" })
        .waitFor();
      await page.getByRole("button", { name: "Batal", exact: true }).click();
      await page.getByRole("button", { name: "Void", exact: true }).click();
      await page
        .getByRole("heading", { name: "Void Saldo Awal", exact: true })
        .waitFor();
      await page
        .getByLabel("Alasan void", { exact: true })
        .fill("Synthetic correction");
      assert.equal(
        await page
          .getByRole("button", { name: "Konfirmasi Void", exact: true })
          .isEnabled(),
        true,
      );
      await page.getByRole("button", { name: "Batal", exact: true }).click();

      for (const [category, amount] of Object.entries({
        POKOK: "200000",
        WAJIB: "125000.50",
        SUKARELA: "30000",
        KHUSUS: "40000",
      })) {
        await page
          .getByLabel(`Simpanan ${category}`, { exact: true })
          .fill(amount);
      }
      const save = page.getByRole("button", {
        name: "Simpan Draft",
        exact: true,
      });
      assert.equal(await save.isDisabled(), true);
      await page.getByRole("button", { name: "Preview", exact: true }).click();
      await page.getByText("Pratinjau per", { exact: false }).waitFor();
      assert.equal(await save.isEnabled(), true);
      await page.getByLabel("Simpanan WAJIB", { exact: true }).fill("1");
      assert.equal(await save.isDisabled(), true);
      assert.equal(
        await page.getByRole("button", { name: "Perhitungan Periode" }).count(),
        0,
      );
      assert.equal(
        await page.getByRole("button", { name: "Saldo Langsung" }).count(),
        0,
      );
      const backLink = page.getByRole("link", { name: "Kembali ke Detail Anggota" });
      assert.equal(await backLink.count(), 1);
      assert.equal(await backLink.getAttribute("href"), "/cooperative/members/1");
      assert.equal(
        await page.getByText("Saldo langsung per 30 Sep 2026", { exact: false }).count(),
        1,
      );
      assert.equal(
        await page.evaluate(
          () => document.documentElement.scrollWidth > window.innerWidth,
        ),
        false,
      );
      assert.deepEqual(errors, []);
      await page.close();
    }
  } finally {
    await browser.close();
  }
});
