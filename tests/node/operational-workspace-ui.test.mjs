import assert from "node:assert/strict";
import fs from "node:fs/promises";
import path from "node:path";
import test from "node:test";
import AxeBuilder from "@axe-core/playwright";
import { chromium, expect } from "@playwright/test";

const member = {
  id: 1,
  name: "Anggota Contoh",
  member_no: "DEMO-001",
  status: "ACTIVE",
  store_account: {
    status: "active",
    balance: 200000,
    credit_limit: 100000,
    available_spending: 300000,
  },
};
const category = {
  id: 1,
  name: "Minuman",
  slug: "minuman",
  is_active: true,
  products_count: 4,
};
const permissions = [
  "manage_cooperative_points",
  "manage_cooperative_rewards",
  "manage_cooperative_redemption",
  "manage_cooperative_shu",
  "access_cooperative_pos",
  "manage_pos_products",
  "view_pos_reports",
];
const paginate = (data) => ({
  data,
  total: data.length,
  from: data.length ? 1 : null,
  to: data.length || null,
  current_page: 1,
  last_page: 1,
  links: [],
});
const longNote =
  "Catatan pesanan yang cukup panjang tetap harus terbaca lengkap, termasuk pilihan tanpa gula dan penggunaan susu alternatif sesuai permintaan anggota.";

function fixtures(empty) {
  const rows = (data) => (empty ? [] : data);
  const products = Array.from({ length: 8 }, (_, i) => ({
    id: i + 1,
    pos_category_id: i < 4 ? 1 : 2,
    name: [
      "Kopi Susu Aren",
      "Americano",
      "Teh Melati",
      "Cokelat Susu",
      "Roti Gandum",
      "Biskuit Kelapa",
      "Air Mineral",
      "Kue Kering",
    ][i],
    sku: `SKU-${i + 1}`,
    barcode: `000000000${i + 1}`,
    sale_price: 25000 + i * 1000,
    cost_price: 12000,
    stock: i === 3 ? 0 : 5,
    minimum_stock: 1,
    image_url: null,
    is_active: true,
    is_discontinued: false,
    category: i < 4 ? category : { id: 2, name: "Makanan" },
  }));
  const analytics = {
    summary: {
      transactions: empty ? 0 : 12,
      voided_transactions: 0,
      gross_sales: empty ? 0 : 1234567890,
      total_discount: 0,
      gross_profit: empty ? 0 : 250000,
      voided_amount: 0,
      returns: { count: 0, total: 0 },
      net_sales: empty ? 0 : 1234567890,
      member_transactions: empty ? 0 : 10,
    },
    payment_reconciliation: rows([
      { method: "TRANSFER", count: 12, total: 1234567890 },
    ]),
    daily_trend: rows([
      { date: "2026-10-27", transactions: 5, revenue: 500000 },
      { date: "2026-10-28", transactions: 7, revenue: 734567890 },
    ]),
    top_products: rows([
      {
        product_name: "Kopi Susu Aren dengan nama produk yang cukup panjang",
        quantity: 12,
        revenue: 1234567890,
        gross_profit: 250000,
        margin_percent: 20,
      },
    ]),
    top_members: rows([
      { member_name: member.name, transactions: 10, total: 1234567890 },
    ]),
    cashier_performance: rows([
      { cashier_name: "Kasir Contoh", transactions: 12, total: 1234567890 },
    ]),
  };
  const allocations = rows([
    {
      member,
      membership_score: 12,
      dues_score: 12,
      shu_score: 24,
      cooperative_shu_amount: 1234567890,
    },
  ]);
  return [
    {
      key: "points",
      component: "Cooperative/Points/Index",
      url: "/cooperative/points",
      props: {
        members: paginate(
          rows([
            {
              ...member,
              total_points: 12345678,
              points_earned: 12350000,
              points_redeemed: 4322,
              member_tier: "GOLD",
              next_tier: null,
              points_to_next_tier: 0,
            },
          ]),
        ),
        stats: {
          active_members: empty ? 0 : 1,
          total_balance: empty ? 0 : 12345678,
        },
        filters: {},
      },
    },
    {
      key: "rewards",
      component: "Cooperative/Rewards/Index",
      url: "/cooperative/rewards",
      props: {
        rewards: paginate(
          rows([
            {
              id: "reward-1",
              name: "Paket Belanja Anggota",
              description: longNote,
              category: "BARANG",
              points_required: 2500,
              stock: 12,
              valid_until: "2026-12-31",
              is_active: true,
            },
            {
              id: "reward-2",
              name: "Voucher Kopi",
              description: null,
              category: "DISKON",
              points_required: 500,
              stock: 0,
              valid_until: null,
              is_active: false,
            },
          ]),
        ),
      },
    },
    {
      key: "redemptions",
      component: "Cooperative/Redemptions/Index",
      url: "/cooperative/redemptions",
      props: {
        redemptions: paginate(
          rows([
            {
              id: "redeem-1",
              status: "PENDING",
              quantity: 2,
              points_used: 5000,
              redeemed_at: "2026-10-28T10:00:00+07:00",
              reward: { name: "Paket Belanja Anggota" },
              member,
            },
          ]),
        ),
      },
    },
    {
      key: "shu",
      component: "Cooperative/Shu/Index",
      url: "/cooperative/shu",
      props: {
        preview: {
          cooperative_pool: empty ? 0 : 1234567890,
          total_shu_score: empty ? 0 : 24,
          total_membership_score: empty ? 0 : 12,
          total_dues_score: empty ? 0 : 12,
          allocations,
        },
        closedPeriod: null,
        filters: {
          year: 2026,
          cooperative_pool: 1234567890,
          pos_profit_pool: "",
        },
      },
    },
    {
      key: "transactions",
      component: "Cooperative/Pos/Transactions/Index",
      url: "/cooperative/pos/transactions",
      props: {
        transactions: paginate(
          rows([
            {
              id: 1,
              transaction_no: "POS-DEMO-001",
              sold_at: "2026-10-28T10:00:00+07:00",
              cashier: { name: "Kasir Contoh" },
              member,
              payments: [{ payment_method: "TRANSFER" }],
              items_count: 3,
              total_amount: 1234567890,
            },
          ]),
        ),
        filters: {},
        cashiers: [{ id: 1, name: "Kasir Contoh" }],
        members: [member],
      },
    },
    {
      key: "register",
      component: "Cooperative/Pos/Register",
      url: "/cooperative/pos",
      props: {
        products: rows(products),
        categories: [category, { id: 2, name: "Makanan" }],
        members: [
          member,
          {
            ...member,
            id: 2,
            name: "Anggota Nonaktif",
            store_account: { ...member.store_account, status: "suspended" },
          },
        ],
      },
    },
    {
      key: "coffee-orders",
      component: "Cooperative/Pos/CoffeeOrders/Index",
      url: "/cooperative/pos/coffee-orders",
      props: {
        orders: paginate(
          rows(
            ["RECEIVED", "BREWING", "READY", "PICKED_UP", "CANCELLED"].map(
              (status, i) => ({
                id: i + 1,
                status,
                status_label: status,
                quantity: 2,
                customization: {
                  sugar_level: "Tanpa gula",
                  ice_level: "Sedikit es",
                  cup_size: "Besar",
                },
                received_at: "2026-10-28T10:00:00+07:00",
                brewing_at:
                  status === "RECEIVED"
                    ? undefined
                    : "2026-10-28T10:05:00+07:00",
                ready_at:
                  status === "READY" ? "2026-10-28T10:10:00+07:00" : undefined,
                notes: longNote,
                transaction: {
                  transaction_no: `POS-DEMO-${i + 1}`,
                  total_amount: 50000,
                  payment_method: "TRANSFER",
                },
                member,
                product: { name: "Kopi Susu Aren" },
                preparer: { name: "Petugas Contoh" },
              }),
            ),
          ),
        ),
        filters: {},
        statuses: ["RECEIVED", "BREWING", "READY", "PICKED_UP", "CANCELLED"],
      },
    },
    {
      key: "reports",
      component: "Cooperative/Pos/Reports/Index",
      url: "/cooperative/pos/reports",
      props: {
        from: "2026-10-01",
        to: "2026-10-28",
        analytics,
        filters: {
          pos_product_id: "",
          category_id: "",
          cashier_id: "",
          payment_method: "",
        },
        products,
        categories: [category],
        cashiers: [{ id: 1, name: "Kasir Contoh" }],
      },
    },
    {
      key: "products",
      component: "Cooperative/Inventory/Products/Index",
      url: "/cooperative/pos-products",
      props: {
        products: paginate(rows(products)),
        categories: [category],
        filters: {},
      },
    },
    {
      key: "categories",
      component: "Cooperative/Inventory/Categories/Index",
      url: "/cooperative/pos-categories",
      props: {
        categories: rows([
          category,
          {
            ...category,
            id: 2,
            name: "Makanan",
            slug: "makanan",
            is_active: false,
          },
        ]),
      },
    },
    {
      key: "counts",
      component: "Cooperative/Inventory/Counts/Index",
      url: "/cooperative/pos/inventory/counts",
      props: {
        counts: paginate(
          rows([
            {
              id: 1,
              count_no: "OPC-DEMO-001",
              counted_at: "2026-10-28",
              status: "DRAFT",
              location: { name: "Toko Utama" },
              requester: { name: "Petugas Contoh" },
            },
            {
              id: 2,
              count_no: "OPC-DEMO-002",
              counted_at: "2026-10-27",
              status: "APPROVED",
              location: { name: "Gudang" },
              requester: { name: "Petugas Contoh" },
            },
          ]),
        ),
      },
    },
    {
      key: "receipts",
      component: "Cooperative/Inventory/Receipts/Index",
      url: "/cooperative/pos/inventory/receipts",
      props: {
        receipts: paginate(
          rows([
            {
              id: 1,
              receipt_no: "RCP-DEMO-001",
              received_at: "2026-10-28",
              total_amount: 1234567890,
              status: "POSTED",
              reference_no: "REF-DEMO-001",
              supplier: { name: "Supplier Contoh" },
              location: { name: "Toko Utama" },
              receiver: { name: "Petugas Contoh" },
            },
          ]),
        ),
      },
    },
    {
      key: "transfers",
      component: "Cooperative/Inventory/Transfers/Index",
      url: "/cooperative/pos/inventory/transfers",
      props: {
        transfers: paginate(
          rows([
            {
              id: 1,
              transfer_no: "TRF-DEMO-001",
              transferred_at: "2026-10-28",
              status: "POSTED",
              fromLocation: { name: "Gudang" },
              toLocation: { name: "Toko Utama" },
              requester: { name: "Petugas Contoh" },
            },
          ]),
        ),
      },
    },
  ];
}

async function requestData(request) {
  if (request.headers()["content-type"]?.includes("multipart/form-data")) {
    const data = await new Response(request.postDataBuffer(), {
      headers: { "content-type": request.headers()["content-type"] },
    }).formData();
    return Object.fromEntries(data);
  }
  return request.postDataJSON();
}

// Every HTTP request is intercepted. Financial and stock interactions below
// operate exclusively on synthetic Inertia responses, never a live server/DB.
test("compact operations: thirteen pages, responsive tables, dialogs and POS contracts", async () => {
  const root = path.resolve("public/build");
  const manifest = JSON.parse(
    await fs.readFile(path.join(root, "manifest.json"), "utf8"),
  );
  const entry = manifest["resources/js/app.ts"];
  const output = path.resolve("test-results/compact-operations");
  await fs.mkdir(output, { recursive: true });
  const browser = await chromium.launch({
    ...(process.env.CI ? {} : { channel: "chrome" }),
    headless: true,
  });
  const failures = [];
  const accessibilityFailures = [];
  try {
    for (const width of [1440, 1280, 768, 390]) {
      for (const empty of [false, true]) {
        const context = await browser.newContext({
          viewport: { width, height: 1000 },
          hasTouch: width === 390,
          locale: "id-ID",
          timezoneId: "Asia/Jakarta",
        });
        const pages = fixtures(empty);
        const errors = [];
        const requests = [];
        const common = {
          auth: {
            user: { id: 99, name: "Admin Demo", email: "admin@example.test" },
            roles: ["Admin Koperasi"],
            permissions: [...permissions],
          },
          csrf_token: "synthetic-csrf",
          flash: {},
          sidebarOpen: true,
        };
        let current;
        let rejectNextWrite = false;
        let deferAnalytics = false;
        const state = () => {
          const result = {
            component: current.component,
            url: current.url,
            version: null,
            props: {
              ...common,
              ...current.props,
              errors: current.errors ?? {},
            },
          };
          if (deferAnalytics && current.key === "reports") {
            delete result.props.analytics;
            result.deferredProps = { default: ["analytics"] };
          }
          return result;
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
          if (url.pathname === "/images/logo_kjy2.png")
            return route.fulfill({
              body: await fs.readFile("public/images/logo_kjy2.png"),
              contentType: "image/png",
            });
          if (url.pathname === "/api/notifications/recent")
            return route.fulfill({
              json: { data: [], meta: { unread_count: 0 } },
            });
          if (["POST", "PUT"].includes(request.method())) {
            const allowed = [
              "/cooperative/rewards",
              "/cooperative/shu/close",
              "/cooperative/pos/transactions",
              "/cooperative/pos-products",
              "/cooperative/pos-products/1",
              "/cooperative/pos-categories",
              "/cooperative/pos-categories/1",
              "/cooperative/pos/coffee-orders/1/status",
            ];
            assert.ok(
              allowed.includes(url.pathname),
              `Unexpected mutation ${url.pathname}`,
            );
            const data = await requestData(request);
            requests.push({
              path: url.pathname,
              method: request.method(),
              data,
            });
            current.errors = rejectNextWrite
              ? {
                  [current.key === "products" ? "sku" : "name"]:
                    "Data contoh ditolak untuk pengujian validasi.",
                }
              : {};
            rejectNextWrite = false;
            if (url.pathname.includes("coffee-orders"))
              current.props.orders.data[0].status = data.status;
            if (url.pathname === "/cooperative/shu/close")
              current.props.closedPeriod = {
                ...current.props.preview,
                status: "CLOSED",
                closed_at: "2026-10-28",
              };
            return route.fulfill({
              json: state(),
              headers: { "X-Inertia": "true" },
            });
          }
          const match = pages.find(
            (item) => new URL(item.url, url.origin).pathname === url.pathname,
          );
          if (match && request.method() === "GET") {
            current = match;
            current.errors = {};
            if (request.headers()["x-inertia"]) {
              requests.push({
                path: url.pathname,
                method: "GET",
                query: Object.fromEntries(url.searchParams),
              });
              current.url = url.pathname + url.search;
              if (deferAnalytics && current.key === "reports") {
                await new Promise((resolve) => setTimeout(resolve, 150));
                deferAnalytics = false;
              }
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
        page.setDefaultTimeout(5000);
        page.on("pageerror", (error) => errors.push(error.message));
        page.on("console", (message) => {
          if (message.type() === "error") errors.push(message.text());
        });
        for (const fixture of pages) {
          errors.length = 0;
          try {
            await page.goto(`http://operations.test${fixture.url}`);
            await expect(page.locator("h1")).toBeVisible();
            if (fixture.key !== "register")
              await expect(page.getByTestId("workspace-summary")).toBeVisible();
            assert.ok(
              await page.evaluate(
                () => document.documentElement.scrollWidth <= innerWidth + 1,
              ),
              `${fixture.key} body overflow at ${width}`,
            );
            if (width === 1440 && !empty) {
              const result = await new AxeBuilder({ page })
                .include("main section")
                .analyze();
              accessibilityFailures.push(
                ...result.violations
                  .filter((item) =>
                    ["serious", "critical"].includes(item.impact),
                  )
                  .map((item) => ({
                    page: fixture.key,
                    id: item.id,
                    nodes: item.nodes.map((node) => node.target),
                  })),
              );
            }
            const info = page.getByRole("button", { name: /^Tentang / });
            await info.focus();
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
                  output,
                  `${fixture.key}-${width}${empty ? "-empty" : ""}.png`,
                ),
                fullPage: true,
                animations: "disabled",
              });
            if (width === 1440) {
              const clipped = await page
                .locator("th, td")
                .evaluateAll((cells) =>
                  cells
                    .filter((cell) => {
                      if (
                        cell.tagName !== "TH" &&
                        !/^(?:-?Rp|[0-9][0-9.,]*$)/.test(
                          cell.textContent.trim(),
                        )
                      )
                        return false;
                      const range = document.createRange();
                      range.selectNodeContents(cell);
                      const text = range.getBoundingClientRect();
                      const box = cell.getBoundingClientRect();
                      return (
                        text.left < box.left - 1 || text.right > box.right + 1
                      );
                    })
                    .map((cell) => cell.textContent.trim()),
                );
              assert.deepEqual(
                clipped,
                [],
                `${fixture.key} clipped header/amount`,
              );
            }
            if (fixture.key === "rewards") {
              await page
                .getByRole("button", { name: "Tambah Reward", exact: true })
                .click();
              const dialog = page.getByRole("dialog");
              await expect(dialog).toBeVisible();
              await expect
                .poll(() =>
                  dialog.evaluate(
                    (element) => getComputedStyle(element).opacity,
                  ),
                )
                .toBe("1");
              if (width === 1440 && !empty) {
                const result = await new AxeBuilder({ page })
                  .include('[role="dialog"]')
                  .analyze();
                accessibilityFailures.push(
                  ...result.violations
                    .filter((item) =>
                      ["serious", "critical"].includes(item.impact),
                    )
                    .map((item) => ({
                      page: `${fixture.key} dialog`,
                      id: item.id,
                      nodes: item.nodes.map((node) => node.target),
                    })),
                );
              }
              await dialog
                .getByLabel("Nama Reward", { exact: true })
                .fill("Reward Contoh");
              await page.keyboard.press("Escape");
              await page
                .getByRole("button", { name: "Tambah Reward", exact: true })
                .click();
              await expect(
                dialog.getByLabel("Nama Reward", { exact: true }),
              ).toHaveValue("Reward Contoh");
              if (width === 1440 && !empty) {
                rejectNextWrite = true;
                await dialog
                  .getByRole("button", { name: "Simpan Reward", exact: true })
                  .click();
                await expect(dialog.getByRole("alert")).toContainText(
                  "Data contoh ditolak",
                );
                await dialog
                  .getByRole("button", { name: "Simpan Reward", exact: true })
                  .click();
                await expect(dialog).toBeHidden();
              } else await page.keyboard.press("Escape");
            }
            if (fixture.key === "transactions") {
              await page
                .getByLabel("No. Transaksi", { exact: true })
                .fill("POS-DEMO");
              await page
                .getByRole("button", { name: "Terapkan Filter", exact: true })
                .click();
              await expect
                .poll(() => requests.at(-1)?.query?.transaction_no)
                .toBe("POS-DEMO");
            }
            if (fixture.key === "shu" && width === 1440 && !empty) {
              await page
                .getByLabel("Pool SHU koperasi", { exact: true })
                .fill("2000000");
              await page
                .getByRole("button", { name: "Preview", exact: true })
                .click();
              await expect
                .poll(() => requests.at(-1)?.query?.cooperative_pool)
                .toBe("2000000");
              await page
                .getByRole("button", { name: "Tutup", exact: true })
                .click();
              await expect(
                page.getByRole("button", { name: "Tutup", exact: true }),
              ).toBeDisabled();
              assert.equal(requests.at(-1).data.cooperative_pool, 2000000);
              common.auth.permissions = common.auth.permissions.filter(
                (permission) => permission !== "manage_cooperative_shu",
              );
              await page.goto(`http://operations.test${fixture.url}`);
              await expect(
                page.getByRole("button", { name: "Tutup", exact: true }),
              ).toHaveCount(0);
            }
            if (fixture.key === "register" && !empty) {
              const catalog = page.getByRole("region", {
                name: "Katalog produk POS",
                exact: true,
              });
              const product = catalog.getByRole("button", {
                name: /Kopi Susu Aren/,
              });
              await product.click();
              await product.click();
              const cart = page.getByRole("complementary", {
                name: "Keranjang POS",
                exact: true,
              });
              await cart
                .getByLabel("Jumlah Kopi Susu Aren", { exact: true })
                .fill("99");
              await expect(
                cart.getByLabel("Jumlah Kopi Susu Aren", { exact: true }),
              ).toHaveValue("5");
              await cart
                .getByLabel("Jumlah Kopi Susu Aren", { exact: true })
                .fill("2");
              await cart
                .getByLabel("Diskon (Rp)", { exact: true })
                .fill("5000");
              await cart
                .getByLabel("Tunai diterima (Rp)", { exact: true })
                .fill("50000");
              await expect(
                cart.getByRole("button", {
                  name: "Bayar Rp 45.000",
                  exact: true,
                }),
              ).toBeEnabled();
              await expect(cart).toContainText("Rp 5.000");
              if (width === 1440)
                await page.screenshot({
                  path: path.join(output, "register-cart-1440.png"),
                  fullPage: true,
                  animations: "disabled",
                });
              await cart
                .getByLabel("Metode pembayaran", { exact: true })
                .selectOption("MEMBER_CREDIT");
              await expect(
                cart.getByRole("button", { name: /^Bayar/ }),
              ).toBeDisabled();
              await cart
                .getByLabel("Anggota pembeli", { exact: true })
                .selectOption("1");
              await expect(
                cart.getByRole("button", { name: /^Bayar/ }),
              ).toBeEnabled();
              await cart
                .getByLabel("Metode pembayaran", { exact: true })
                .selectOption("MEMBER_STORE_ACCOUNT");
              await expect(
                cart.getByRole("button", { name: /^Bayar/ }),
              ).toBeDisabled();
              await cart
                .getByLabel("Yang berbelanja", { exact: true })
                .fill("Pembeli Contoh");
              await expect(
                cart.getByRole("button", { name: /^Bayar/ }),
              ).toBeEnabled();
              await cart
                .getByLabel("Anggota pembeli", { exact: true })
                .selectOption("2");
              await expect(
                cart.getByRole("button", { name: /^Bayar/ }),
              ).toBeDisabled();
              await cart
                .getByLabel("Metode pembayaran", { exact: true })
                .selectOption("CASH");
              if (width === 1440) {
                const beforeDismiss = requests.length;
                page.once("dialog", (dialog) => dialog.dismiss());
                await cart.getByRole("button", { name: /^Bayar/ }).click();
                assert.equal(requests.length, beforeDismiss);
                page.once("dialog", (dialog) => dialog.accept());
                await cart.getByRole("button", { name: /^Bayar/ }).click();
                await expect(
                  cart.getByText("Belum ada item.", { exact: true }),
                ).toBeVisible();
                const paid = requests.at(-1).data;
                assert.equal(paid.discount_amount, 5000);
                assert.equal(paid.cash_received, 50000);
                assert.deepEqual(paid.items, [
                  { pos_product_id: 1, quantity: 2 },
                ]);
                assert.match(paid.client_reference, /^WEB-/);
              }
            }
            if (fixture.key === "coffee-orders") {
              await page
                .getByLabel("Cari pesanan kopi", { exact: true })
                .fill("Aren");
              await page
                .getByRole("button", { name: "Terapkan", exact: true })
                .click();
              await expect
                .poll(() => requests.at(-1)?.query?.search)
                .toBe("Aren");
              if (width === 1440 && !empty) {
                await page
                  .getByRole("button", { name: "Mulai Seduh", exact: true })
                  .click();
                await expect
                  .poll(() => requests.at(-1)?.data?.status)
                  .toBe("BREWING");
                await expect(
                  page.getByRole("button", {
                    name: "Mulai Seduh",
                    exact: true,
                  }),
                ).toHaveCount(0);
              }
            }
            if (fixture.key === "reports") {
              await page
                .getByLabel("Metode", { exact: true })
                .selectOption("TRANSFER");
              await page
                .getByRole("button", { name: "Terapkan", exact: true })
                .click();
              await expect
                .poll(() => requests.at(-1)?.query?.payment_method)
                .toBe("TRANSFER");
              const csv = new URL(
                await page
                  .getByRole("link", { name: "CSV", exact: true })
                  .getAttribute("href"),
                "http://operations.test",
              );
              assert.equal(csv.searchParams.get("payment_method"), "TRANSFER");
              assert.equal(csv.searchParams.get("from"), "2026-10-01");
              if (width === 1440 && empty) {
                deferAnalytics = true;
                await page.goto(`http://operations.test${fixture.url}`);
                await expect(
                  page.getByTestId("workspace-summary"),
                ).toBeVisible();
                assert.equal(deferAnalytics, false);
              }
            }
            if (fixture.key === "products") {
              await page
                .getByRole("button", { name: "Tambah Produk", exact: true })
                .click();
              const dialog = page.getByRole("dialog");
              await expect(dialog).toBeVisible();
              await expect
                .poll(() =>
                  dialog.evaluate(
                    (element) => getComputedStyle(element).opacity,
                  ),
                )
                .toBe("1");
              if (width === 1440 && !empty) {
                const result = await new AxeBuilder({ page })
                  .include('[role="dialog"]')
                  .analyze();
                accessibilityFailures.push(
                  ...result.violations
                    .filter((item) =>
                      ["serious", "critical"].includes(item.impact),
                    )
                    .map((item) => ({
                      page: `${fixture.key} dialog`,
                      id: item.id,
                      nodes: item.nodes.map((node) => node.target),
                    })),
                );
              }
              await expect(
                dialog.getByLabel("Nama Produk", { exact: true }),
              ).toBeVisible();
              await dialog
                .getByRole("button", { name: "Batal", exact: true })
                .click();
              if (!empty) {
                await page
                  .getByRole("button", { name: "Edit", exact: true })
                  .first()
                  .click();
                await expect(
                  dialog.getByLabel("Nama Produk", { exact: true }),
                ).toHaveValue("Kopi Susu Aren");
                await expect(
                  dialog.getByLabel("Stok Awal", { exact: true }),
                ).toBeDisabled();
                if (width === 1440) {
                  await dialog
                    .getByRole("button", { name: "Simpan", exact: true })
                    .click();
                  await expect(dialog).toBeHidden();
                  assert.equal(requests.at(-1).data._method, "PUT");
                  await page
                    .getByRole("button", { name: "Tambah Produk", exact: true })
                    .click();
                  await dialog
                    .getByLabel("Nama Produk", { exact: true })
                    .fill("Produk Baru");
                  await dialog
                    .getByLabel("SKU", { exact: true })
                    .fill("SKU-DEMO-NEW");
                  rejectNextWrite = true;
                  await dialog
                    .getByRole("button", { name: "Simpan", exact: true })
                    .click();
                  await expect(dialog.getByRole("alert")).toContainText(
                    "Data contoh ditolak",
                  );
                  assert.equal(
                    requests.at(-1).path,
                    "/cooperative/pos-products",
                  );
                  assert.equal(requests.at(-1).data._method, undefined);
                }
                await page.keyboard.press("Escape");
              }
            }
            if (fixture.key === "categories") {
              await page
                .getByRole("button", { name: "Tambah Kategori", exact: true })
                .click();
              const dialog = page.getByRole("dialog");
              await expect(dialog).toBeVisible();
              await expect
                .poll(() =>
                  dialog.evaluate(
                    (element) => getComputedStyle(element).opacity,
                  ),
                )
                .toBe("1");
              if (width === 1440 && !empty) {
                const result = await new AxeBuilder({ page })
                  .include('[role="dialog"]')
                  .analyze();
                accessibilityFailures.push(
                  ...result.violations
                    .filter((item) =>
                      ["serious", "critical"].includes(item.impact),
                    )
                    .map((item) => ({
                      page: `${fixture.key} dialog`,
                      id: item.id,
                      nodes: item.nodes.map((node) => node.target),
                    })),
                );
              }
              await expect(
                dialog.getByLabel("Nama", { exact: true }),
              ).toBeVisible();
              await page.keyboard.press("Escape");
              if (!empty) {
                await page
                  .getByRole("button", { name: "Edit", exact: true })
                  .first()
                  .click();
                await expect(
                  dialog.getByLabel("Nama", { exact: true }),
                ).toHaveValue("Minuman");
                await page.keyboard.press("Escape");
              }
            }
            if (
              !empty &&
              ((fixture.key === "products" && width === 1440) ||
                (fixture.key === "coffee-orders" && width === 390))
            ) {
              const data =
                fixture.key === "products"
                  ? fixture.props.products
                  : fixture.props.orders;
              const next = new URL(fixture.url, "http://operations.test");
              next.searchParams.set("page", "2");
              data.last_page = 2;
              data.total = data.data.length * 2;
              data.links = [
                { url: null, label: "&laquo; Previous", active: false },
                { url: fixture.url, label: "1", active: true },
                { url: next.pathname + next.search, label: "2", active: false },
                {
                  url: next.pathname + next.search,
                  label: "Next &raquo;",
                  active: false,
                },
              ];
              await page.goto(`http://operations.test${fixture.url}`);
              const pager = page.getByRole("navigation", {
                name:
                  fixture.key === "products"
                    ? "Halaman katalog produk"
                    : "Halaman pesanan kopi",
                exact: true,
              });
              await expect(pager).toBeVisible();
              await pager
                .getByRole("link", { name: "Berikutnya", exact: true })
                .click();
              await expect.poll(() => requests.at(-1)?.query?.page).toBe("2");
            }
            assert.deepEqual(errors, [], `${fixture.key} browser errors`);
          } catch (error) {
            failures.push({
              page: fixture.key,
              width,
              empty,
              error: error.message,
            });
            await page
              .screenshot({
                path: path.join(
                  output,
                  `failure-${fixture.key}-${width}${empty ? "-empty" : ""}.png`,
                ),
                animations: "disabled",
              })
              .catch(() => {});
          }
        }
        await context.close();
      }
    }
    await fs.writeFile(
      path.join(output, "verification.json"),
      JSON.stringify({ failures, accessibilityFailures }, null, 2),
    );
    assert.deepEqual(failures, [], "Operational UI scenarios");
    assert.deepEqual(
      accessibilityFailures,
      [],
      "Operational UI serious/critical accessibility",
    );
  } finally {
    await browser.close();
  }
});
