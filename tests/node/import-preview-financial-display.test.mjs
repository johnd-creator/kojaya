import assert from "node:assert/strict";
import fs from "node:fs/promises";
import path from "node:path";
import test from "node:test";
import { chromium } from "@playwright/test";

test("ImportPreview renders exact large financial totals and boundary decimals in browser", async () => {
  const root = path.resolve("public/build");
  const manifest = JSON.parse(
    await fs.readFile(path.join(root, "manifest.json"), "utf8"),
  );
  const entry = manifest["resources/js/app.ts"];
  const url = "http://qar.test/cooperative/members/import";

  const inertia = {
    component: "Cooperative/Members/ImportPreview",
    url,
    version: null,
    props: {
      auth: {
        user: {
          id: 1,
          name: "Admin Koperasi",
          email: "admin@example.test",
        },
        roles: ["Admin Koperasi"],
        permissions: ["manage_cooperative_members"],
      },
      csrf_token: "synthetic-csrf",
      flash: {},
      sidebarOpen: false,
      is_global: false,
      current_organization_id: "org-1",
      organizations: [{ id: "org-1", code: "KOP", name: "Koperasi Test" }],
      default_import_date: "2026-10-09",
      default_cutoff_date: "2026-09-30",
      canonical_headers: [
        "member_number",
        "full_name",
        "email",
        "phone_number",
        "identity_number",
        "gender",
        "company_code",
        "employee_number",
        "address",
        "membership_type",
        "join_date",
        "notes",
        "opening_balance_pokok",
        "opening_balance_wajib",
        "opening_balance_sukarela",
        "opening_balance_khusus",
      ],
      canonical_headers_v1: [
        "member_number",
        "full_name",
        "email",
        "phone_number",
        "identity_number",
        "gender",
        "company_code",
        "employee_number",
        "address",
        "membership_type",
        "join_date",
        "notes",
      ],
      canonical_headers_v2: [
        "member_number",
        "full_name",
        "email",
        "phone_number",
        "identity_number",
        "gender",
        "company_code",
        "employee_number",
        "address",
        "membership_type",
        "join_date",
        "notes",
        "opening_balance_pokok",
        "opening_balance_wajib",
        "opening_balance_sukarela",
        "opening_balance_khusus",
      ],
      preview_proof: "proof-123456",
      preview: {
        valid: true,
        header_valid: true,
        csv_version: "V2",
        opening_balance_cutoff_date: "2026-09-30",
        total_rows: 1,
        valid_rows: 1,
        invalid_rows: 0,
        manual_review_required: false,
        errors: [],
        batch_errors: [],
        opening_balance_summary: {
          version: "V2",
          total_members: 1,
          members_with_positive_balance_count: 1,
          total_pokok: "99999999999999.99",
          total_wajib: "100000.50",
          total_sukarela: "0.30",
          total_khusus: "0.10",
          grand_total: "99999999999999.99",
          cutoff_date: "2026-09-30",
        },
        rows: [
          {
            row_number: 1,
            valid: true,
            persistable: true,
            manual_review_required: false,
            employee_resolution_status: "RESOLVED",
            errors: [],
            normalized_data: {
              member_number: "KOP-001",
              full_name: "Anggota Uji",
              email: "uji@example.test",
              phone_number: "08123456789",
              membership_type: "REGULAR",
              join_date: "2026-01-01",
              employee_number: "EMP-001",
              opening_balance_pokok: "99999999999999.99",
              opening_balance_wajib: "100000.50",
              opening_balance_sukarela: "0.30",
              opening_balance_khusus: "0.10",
            },
          },
        ],
      },
    },
  };

  const encoded = JSON.stringify(inertia)
    .replaceAll("&", "&amp;")
    .replaceAll('"', "&quot;");
  const html = `<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1">${(entry.css ?? []).map((file) => `<link rel="stylesheet" href="/build/${file}">`).join("")}</head><body><div id="app" data-page="${encoded}"></div><script type="module" src="/build/${entry.file}"></script></body></html>`;

  const browser = await chromium.launch({ channel: "chrome", headless: true });
  try {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
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
      } else if (pathname === "/cooperative/members/import") {
        await route.fulfill({ body: html, contentType: "text/html" });
      } else {
        await route.abort();
      }
    });

    await page.goto(url);
    await page.getByText("Ringkasan Saldo Awal", { exact: false }).waitFor();

    // Verify exact grand total rendering
    const grandTotalElements = await page.getByText("Rp 99.999.999.999.999,99").all();
    assert.ok(grandTotalElements.length >= 1, "Expected exact formatted grand total 'Rp 99.999.999.999.999,99' in DOM");

    // Verify corrupted IEEE-754 precision value does NOT appear in DOM
    const corruptedElements = await page.getByText("99.999.999.999.999,98").all();
    assert.equal(corruptedElements.length, 0, "Corrupted floating-point value ',98' must NOT exist in DOM");

    // Verify boundary decimal amounts
    const wajibTotal = await page.getByText("Rp 100.000,50").all();
    assert.ok(wajibTotal.length >= 1, "Expected exact formatted wajib total 'Rp 100.000,50'");

    const sukarelaTotal = await page.getByText("Rp 0,30").all();
    assert.ok(sukarelaTotal.length >= 1, "Expected exact formatted sukarela total 'Rp 0,30'");

    const khususTotal = await page.getByText("Rp 0,10").all();
    assert.ok(khususTotal.length >= 1, "Expected exact formatted khusus total 'Rp 0,10'");

    assert.deepEqual(errors, []);
    await page.close();
  } finally {
    await browser.close();
  }
});
