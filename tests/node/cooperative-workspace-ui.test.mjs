import assert from "node:assert/strict";
import fs from "node:fs/promises";
import path from "node:path";
import test from "node:test";
import AxeBuilder from "@axe-core/playwright";
import { chromium, expect } from "@playwright/test";

const member = { id: 1, name: "Anggota Contoh", member_no: "DEMO-001" };
const description =
  "Pembayaran simpanan sukarela periode Oktober melalui transfer bank. Keterangan panjang ini harus terbaca lengkap dan membungkus ke baris berikutnya tanpa terpotong.";
const loanType = {
  id: 1,
  name: "Pinjaman Produktif",
  code: "productive",
  description,
  interest_rate: 1.25,
  admin_fee: 25000,
  late_fee_per_day: 2500,
  min_amount: 1000000,
  max_amount: 25000000,
  min_term_months: 3,
  max_term_months: 24,
  is_active: true,
};
const paginate = (data) => ({
  data,
  total: data.length,
  from: data.length ? 1 : null,
  to: data.length || null,
  current_page: 1,
  last_page: 1,
  links: [],
});
const schedule = [
  {
    installment_no: 1,
    due_date: "2026-11-28",
    principal_amount: 1000000,
    interest_amount: 150000,
    fee_amount: 25000,
    amount_due: 1175000,
  },
];
const preview = {
  installment_amount: 1150000,
  total_interest_amount: 1800000,
  admin_fee: 25000,
  total_amount: 13825000,
  schedule,
};

function fixtures(empty = false, manage = true) {
  const rows = (items) => (empty ? [] : items);
  const accounts = rows([
    {
      id: 1,
      member,
      balance: -850000,
      credit_limit: 1000000,
      available_credit: 150000,
      status: "active",
      status_label: "Aktif",
      is_negative: true,
    },
    {
      id: 2,
      member: {
        ...member,
        id: 2,
        name: "Anggota Kedua",
        member_no: "DEMO-002",
      },
      balance: 250000,
      credit_limit: 0,
      available_credit: 0,
      status: "suspended",
      status_label: "Ditangguhkan",
      is_negative: false,
    },
  ]);
  return [
    {
      key: "payments",
      url: "/cooperative/payments?status=PENDING",
      component: "Cooperative/Payments/Index",
      props: {
        payments: paginate(
          rows(
            ["PENDING", "APPROVED"].map((status, i) => ({
              id: i + 1,
              status,
              paid_at: "2026-10-28",
              payment_method: "TRANSFER",
              amount: 1234567890,
              notes: description,
              member,
              contribution_type: { name: "Simpanan Sukarela" },
              can_cancel: status === "PENDING" && manage,
              has_proof: false,
            })),
          ),
        ),
        members: [member],
        contributionTypes: [
          {
            id: 1,
            code: "SUKARELA",
            name: "Simpanan Sukarela",
            category: "SUKARELA",
            default_amount: 0,
          },
        ],
        filters: { status: "PENDING" },
        canApprovePayments: manage,
      },
    },
    {
      key: "ledger",
      url: "/cooperative/ledger",
      component: "Cooperative/Ledger/Index",
      props: {
        entries: paginate(
          rows([
            {
              id: 1,
              posted_at: "2026-10-28",
              period: "2026-10",
              member,
              contribution_type: {
                name: "Simpanan Sukarela",
                code: "SUKARELA",
              },
              category_snapshot: "SUKARELA",
              entry_type: "SAVING_PAYMENT",
              ledger_scope: "SAVINGS",
              debit: 0,
              credit: 1234567890,
              description,
              cooperative_payment_id: 1,
            },
          ]),
        ),
        filters: { ledger_scope: "SAVINGS" },
        summary: {
          total_balance: empty ? 0 : 1234567890,
          by_category: { SUKARELA: empty ? 0 : 1234567890 },
          uncategorized: 0,
        },
        contributionTypes: [
          {
            id: 1,
            name: "Simpanan Sukarela",
            code: "SUKARELA",
            category: "SUKARELA",
          },
        ],
        categories: ["SUKARELA"],
        entryTypes: ["SAVING_PAYMENT"],
        canManageLedger: manage,
        canCorrectLedgerPayment: manage,
      },
    },
    {
      key: "withdrawals",
      url: "/cooperative/savings/withdrawals",
      component: "Cooperative/Savings/Withdrawals/Index",
      props: {
        withdrawals: paginate(
          rows(
            ["PENDING", "PROCESSED", "REJECTED"].map((status, i) => ({
              id: i + 1,
              cooperative_member_id: 1,
              member,
              amount: 1000000,
              status,
              destination_bank: "Bank Contoh",
              destination_account_no: "0000000000",
              destination_account_name: "Anggota Contoh",
              reason: "Kebutuhan pendidikan keluarga",
              rejection_reason:
                status === "REJECTED"
                  ? "Rekening tujuan perlu diverifikasi ulang"
                  : null,
              created_at: "2026-10-28T10:00:00+07:00",
              available_voluntary_balance: 2000000,
              projected_remaining_balance: 1000000,
            })),
          ),
        ),
      },
    },
    {
      key: "loans",
      url: "/cooperative/loans",
      component: "Cooperative/Loans/Index",
      props: {
        loans: paginate(
          rows([
            {
              id: 1,
              member,
              loan_type: loanType,
              status: "ACTIVE",
              principal_amount: 12000000,
              outstanding_amount: 9000000,
              first_due_date: "2026-11-28",
              applied_at: "2026-10-28",
              term_months: 12,
            },
          ]),
        ),
        members: [member],
        loanTypes: [loanType],
        filters: {},
        stats: {
          applied: 0,
          manager_approved: 0,
          active: empty ? 0 : 1,
          paid_off: 0,
        },
      },
    },
    {
      key: "calculator",
      url: "/cooperative/loans/calculator",
      component: "Cooperative/Loans/Calculator",
      props: {
        loanTypes: [loanType],
        input: {
          loan_type_id: "1",
          principal_amount: "12000000",
          term_months: "12",
          first_due_date: "2026-11-28",
        },
        preview: empty ? null : preview,
      },
    },
    {
      key: "loan-types",
      url: "/cooperative/loan-types",
      component: "Cooperative/Loans/Types",
      props: {
        loanTypes: rows([
          loanType,
          {
            ...loanType,
            id: 2,
            name: "Pinjaman Darurat",
            code: "emergency",
            is_active: false,
          },
        ]),
      },
    },
    {
      key: "store-credit",
      url: "/cooperative/store-credit",
      component: "Cooperative/StoreCredit/Index",
      props: {
        accounts: {
          data: accounts,
          links: {},
          meta: { ...paginate(accounts), data: undefined, per_page: 15 },
        },
        filters: {},
        eligibleMembers: [member],
        canManage: manage,
      },
    },
    {
      key: "store-credit-report",
      url: "/cooperative/store-credit-report",
      component: "Cooperative/StoreCredit/Report",
      props: {
        summary: {
          positive_deposit_liability: empty ? 0 : 250000,
          negative_receivable: empty ? 0 : 850000,
          positive_account_count: empty ? 0 : 1,
          zero_account_count: 0,
          negative_account_count: empty ? 0 : 1,
          suspended_account_count: empty ? 0 : 1,
          utilization_threshold: 0.8,
          high_utilization_accounts: rows([
            {
              id: 1,
              cooperative_member_id: 1,
              balance: -850000,
              credit_limit: 1000000,
              utilization: 0.85,
            },
          ]),
          oldest_uncovered_debt_date: empty ? null : "2026-10-01",
        },
      },
    },
  ];
}

// The production bundle receives synthetic Inertia props. Every request is
// intercepted; these tests never connect to QA or an application database.
test("cooperative workspaces retain readable tables, dialogs and filters across viewports", async () => {
  const root = path.resolve("public/build");
  const manifest = JSON.parse(
    await fs.readFile(path.join(root, "manifest.json"), "utf8"),
  );
  const entry = manifest["resources/js/app.ts"];
  const browser = await chromium.launch({
    ...(process.env.CI ? {} : { channel: "chrome" }),
    headless: true,
  });
  const screenshots = path.resolve("test-results/compact-cooperative");
  await fs.mkdir(screenshots, { recursive: true });
  const accessibilityFailures = [];
  try {
    for (const width of [1440, 1280, 768, 390]) {
      for (const empty of [false, true]) {
        const context = await browser.newContext({
          viewport: { width, height: 1000 },
          hasTouch: width === 390,
        });
        const errors = [];
        const requests = [];
        const pages = fixtures(empty);
        let current;
        const common = {
          auth: {
            user: { id: 99, name: "Admin Demo", email: "admin@example.test" },
            roles: ["Admin Koperasi"],
            permissions: [
              "manage_cooperative_payment",
              "manage_cooperative_dues",
              "manage_cooperative_loan",
              "manage_cooperative_loan_type",
              "manage_cooperative_ledger",
              "manage_member_store_credit",
            ],
          },
          flash: {},
          errors: {},
          sidebarOpen: true,
          csrf_token: "synthetic-csrf",
        };
        const state = () => ({
          component: current.component,
          url: current.url,
          version: null,
          props: { ...common, ...current.props },
        });
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
          if (url.pathname === "/images/logo_kjy2.png")
            return route.fulfill({
              body: await fs.readFile("public/images/logo_kjy2.png"),
              contentType: "image/png",
            });
          if (url.pathname === "/api/notifications/recent")
            return route.fulfill({
              json: { data: [], meta: { unread_count: 0 } },
            });
          const match = pages.find(
            (item) => new URL(item.url, url.origin).pathname === url.pathname,
          );
          if (match && request.method() === "GET") {
            current = match;
            if (request.headers()["x-inertia"]) {
              requests.push({
                path: url.pathname,
                query: Object.fromEntries(url.searchParams),
              });
              current.url = url.pathname + url.search;
              if (current.key === "calculator") current.props.preview = preview;
              return route.fulfill({
                json: state(),
                headers: { "X-Inertia": "true" },
              });
            }
            const encoded = JSON.stringify(state())
              .replaceAll("&", "&amp;")
              .replaceAll('"', "&quot;");
            return route.fulfill({
              body: `<!doctype html><html lang="id"><head><meta name="viewport" content="width=device-width, initial-scale=1">${(entry.css ?? []).map((file) => `<link rel="stylesheet" href="/build/${file}">`).join("")}</head><body><div id="app" data-page="${encoded}"></div><script type="module" src="/build/${entry.file}"></script></body></html>`,
              contentType: "text/html",
            });
          }
          errors.push(`Unexpected request ${request.method()} ${url.pathname}`);
          return route.abort();
        });
        const page = await context.newPage();
        page.setDefaultTimeout(7000);
        page.on("pageerror", (error) => errors.push(error.message));
        page.on("console", (message) => {
          if (message.type() === "error") errors.push(message.text());
        });
        for (const fixture of pages) {
          await page.goto(`http://workspace.test${fixture.url}`);
          await expect(page.locator("h1")).toBeVisible();
          if (fixture.key !== "calculator" || !empty)
            await expect(page.getByTestId("workspace-summary")).toBeVisible();
          assert.ok(
            await page.evaluate(
              () => document.documentElement.scrollWidth <= innerWidth + 1,
            ),
            `${fixture.key} overflows at ${width}`,
          );
          if (width === 1440 && !empty) {
            const accessibility = await new AxeBuilder({ page })
              .include("main section")
              .analyze();
            accessibilityFailures.push(
              ...accessibility.violations
                .filter((item) => ["serious", "critical"].includes(item.impact))
                .map((item) => ({
                  page: fixture.key,
                  id: item.id,
                  nodes: item.nodes.map((node) => node.target),
                })),
            );
            const summary = page.getByTestId("workspace-summary");
            if (await summary.count()) {
              const widths = await summary
                .locator(":scope > div")
                .evaluateAll((items) =>
                  items.map((item) =>
                    Math.round(item.getBoundingClientRect().width),
                  ),
                );
              assert.ok(
                Math.max(...widths) - Math.min(...widths) <= 1,
                `${fixture.key} summary widths ${widths}`,
              );
            }
          }
          const tooltip = page.getByRole("button", { name: /^Tentang / });
          await tooltip.focus();
          await expect(
            page.locator('[data-slot="tooltip-content"]'),
          ).toBeVisible();
          await page.keyboard.press("Escape");
          await page.locator("h1").click();
          await expect(
            page.locator('[data-slot="tooltip-content"]'),
          ).toBeHidden();
          if (width === 1440 || width === 390)
            await page.screenshot({
              path: path.join(
                screenshots,
                `${fixture.key}-${width}${empty ? "-empty" : ""}.png`,
              ),
              fullPage: true,
              animations: "disabled",
            });
          if (fixture.key === "payments") {
            assert.equal(
              await page.getByLabel("Ketik nama atau nomor anggota").count(),
              0,
            );
            const card = page.getByTestId("payment-history-card");
            if (width >= 1280)
              assert.ok(
                (await card.boundingBox()).width > width - 340,
                "payment history should use the available workspace width",
              );
            await page
              .getByRole("button", { name: "Catat Pembayaran", exact: true })
              .click();
            const dialog = page.getByRole("dialog");
            await expect(
              dialog.getByLabel("Anggota", { exact: true }),
            ).toBeVisible();
            await dialog.getByLabel("Anggota", { exact: true }).fill("Contoh");
            await dialog
              .getByRole("button", { name: /Anggota Contoh/ })
              .press("Enter");
            await dialog
              .getByLabel("Jenis Simpanan", { exact: true })
              .selectOption("1");
            await dialog.getByLabel("Nominal", { exact: true }).fill("250000");
            await page.keyboard.press("Escape");
            await page
              .getByRole("button", { name: "Catat Pembayaran", exact: true })
              .click();
            await expect(
              dialog.getByLabel("Nominal", { exact: true }),
            ).toHaveValue("250000");
            await page.keyboard.press("Escape");
            await page
              .getByLabel("Cari pembayaran", { exact: true })
              .fill("Contoh");
            await page
              .getByRole("button", { name: "Terapkan filter", exact: true })
              .click();
            await expect
              .poll(() => requests.at(-1)?.query.search)
              .toBe("Contoh");
          }
          if (fixture.key === "ledger") {
            if (width === 1440) {
              const clipped = await page.locator("th").evaluateAll((headers) =>
                headers
                  .filter((header) => {
                    const range = document.createRange();
                    range.selectNodeContents(header);
                    return (
                      range.getBoundingClientRect().right >
                      header.getBoundingClientRect().right + 1
                    );
                  })
                  .map((header) => header.textContent.trim()),
              );
              assert.deepEqual(
                clipped,
                [],
                "Ledger headers must be fully readable",
              );
            }
            await expect(
              page.getByRole("columnheader", { name: "Scope", exact: true }),
            ).toHaveCount(0);
            if (!empty) {
              const text = page.getByText(description, { exact: true });
              await expect(text).toBeVisible();
              assert.equal(
                await text.evaluate((el) => getComputedStyle(el).whiteSpace),
                "normal",
              );
              assert.ok(
                await text.evaluate(
                  (el) =>
                    el.scrollHeight <= el.clientHeight + 1 &&
                    el.scrollWidth <= el.clientWidth + 1,
                ),
                "ledger description must be fully readable",
              );
            }
            if (width >= 1280) {
              const box = await page
                .getByTestId("ledger-filter-card")
                .boundingBox();
              assert.ok(box.height < 90, `ledger filter height ${box.height}`);
            }
          }
          if (fixture.key === "withdrawals" && !empty) {
            const visible = page.locator(
              width >= 768 ? "table" : ".md\\:hidden",
            );
            await visible
              .getByRole("button", { name: "Setujui", exact: true })
              .first()
              .click();
            await expect(page.getByRole("dialog")).toContainText(
              "Proyeksi Sisa Saldo",
            );
            await page.keyboard.press("Escape");
            await visible
              .getByRole("button", { name: "Tolak", exact: true })
              .first()
              .click();
            await expect(
              page
                .getByRole("dialog")
                .getByRole("button", { name: "Tolak Pengajuan", exact: true }),
            ).toBeDisabled();
            await page.keyboard.press("Escape");
            await page
              .getByLabel("Status penarikan di halaman ini")
              .selectOption("PROCESSED");
            await expect(
              visible.getByRole("button", { name: "Setujui", exact: true }),
            ).toHaveCount(0);
          }
          if (fixture.key === "calculator") {
            if (empty)
              await expect(
                page.getByTestId("calculator-placeholder"),
              ).toBeVisible();
            await page
              .getByLabel("Nominal Pokok", { exact: true })
              .fill("6000000");
            await page.getByRole("button", { name: "Hitung Simulasi" }).click();
            await expect
              .poll(() => requests.at(-1)?.query.principal_amount)
              .toBe("6000000");
            await expect(
              page.getByRole("heading", { name: "Jadwal Simulasi" }),
            ).toBeVisible();
          }
          if (fixture.key === "loan-types") {
            const auditDarkDialog = width === 1440 && !empty;
            if (auditDarkDialog) {
              await page.evaluate(() =>
                document.documentElement.classList.add("dark"),
              );
            }
            await page
              .getByRole("button", {
                name: "Tambah Tipe Pinjaman",
                exact: true,
              })
              .click();
            await expect(
              page.getByRole("dialog").getByLabel("Kode", { exact: true }),
            ).toBeVisible();
            if (auditDarkDialog) {
              await page.waitForFunction(() => {
                const dialog = document.querySelector('[role="dialog"]');
                return dialog && getComputedStyle(dialog).opacity === "1";
              });
              const accessibility = await new AxeBuilder({ page })
                .include("#main-content")
                .include('[role="dialog"]')
                .analyze();
              accessibilityFailures.push(
                ...accessibility.violations.filter((violation) =>
                  ["serious", "critical"].includes(violation.impact ?? ""),
                ),
              );
            }
            await page.keyboard.press("Escape");
            if (auditDarkDialog) {
              await page.evaluate(() =>
                document.documentElement.classList.remove("dark"),
              );
            }
            if (!empty) {
              await page
                .getByRole("button", { name: "Edit", exact: true })
                .first()
                .click();
              await expect(
                page
                  .getByRole("dialog")
                  .getByLabel("Nama tipe", { exact: true }),
              ).toHaveValue("Pinjaman Produktif");
              await page.keyboard.press("Escape");
            }
          }
          if (fixture.key === "store-credit") {
            await expect(
              page.getByText(
                empty
                  ? "Belum ada akun saldo toko pada filter ini."
                  : member.name,
                { exact: true },
              ),
            ).toBeVisible();
            await page
              .getByRole("button", { name: "Buka Akun", exact: true })
              .click();
            await expect(page.getByRole("dialog")).toContainText(
              "Limit Kredit",
            );
            await page.keyboard.press("Escape");
            await page.getByLabel("Cari akun anggota").fill("Contoh");
            await page
              .getByRole("button", { name: "Terapkan", exact: true })
              .click();
            await expect.poll(() => requests.at(-1)?.query.q).toBe("Contoh");
          }
          if (
            width === 1440 &&
            !empty &&
            ["payments", "ledger", "store-credit"].includes(fixture.key)
          ) {
            if (fixture.key === "payments")
              fixture.props.canApprovePayments = false;
            if (fixture.key === "ledger")
              fixture.props.canCorrectLedgerPayment = false;
            if (fixture.key === "store-credit") fixture.props.canManage = false;
            await page.goto(`http://workspace.test${fixture.url}`);
            if (fixture.key === "payments")
              await expect(
                page.getByRole("button", {
                  name: "Approve pembayaran",
                  exact: true,
                }),
              ).toHaveCount(0);
            if (fixture.key === "ledger")
              await expect(
                page.getByRole("columnheader", { name: "Aksi", exact: true }),
              ).toHaveCount(0);
            if (fixture.key === "store-credit")
              await expect(
                page.getByRole("button", { name: "Buka Akun", exact: true }),
              ).toHaveCount(0);
          }
          assert.deepEqual(
            errors,
            [],
            `${fixture.key} browser errors at ${width}`,
          );
        }
        await context.close();
      }
    }
    assert.deepEqual(accessibilityFailures, [], "Workspace accessibility");
  } finally {
    await browser.close();
  }
});
