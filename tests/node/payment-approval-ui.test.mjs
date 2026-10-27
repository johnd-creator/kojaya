import assert from "node:assert/strict";
import fs from "node:fs/promises";
import path from "node:path";
import test from "node:test";
import { chromium } from "@playwright/test";

test("payment proof tabs, cancellation and approval errors on desktop/tablet/mobile", async () => {
  const root = path.resolve("public/build");
  const manifest = JSON.parse(
    await fs.readFile(path.join(root, "manifest.json"), "utf8"),
  );
  const entry = manifest["resources/js/app.ts"];
  const browser = await chromium.launch({ channel: "chrome", headless: true });
  try {
    for (const viewport of [
      { width: 1440, height: 900 },
      { width: 768, height: 1024 },
      { width: 390, height: 844 },
    ]) {
      const context = await browser.newContext({ viewport });
      const errors = [];
      const requests = [];
      const rows = [1, 2, 3].map((id) => ({
        id,
        status: id === 3 ? "APPROVED" : "PENDING",
        paid_at: "2026-10-27",
        payment_method: "TRANSFER",
        amount: "100000.00",
        has_proof: true,
        can_view_proof: true,
        can_cancel: id !== 3,
        member: { name: `Synthetic member ${id}`, member_no: `TEST-${id}` },
        invoice: { contribution_type: { name: "Simpanan Wajib" } },
      }));
      const state = {
        component: "Cooperative/Payments/Index",
        url: "/cooperative/payments?status=PENDING",
        version: null,
        props: {
          auth: {
            user: {
              id: 99,
              name: "Synthetic admin",
              email: "admin@example.test",
            },
            roles: ["Admin Koperasi"],
            permissions: [
              "manage_cooperative_payment",
              "verify_cooperative_member",
            ],
          },
          csrf_token: "synthetic-csrf",
          flash: {},
          errors: {},
          sidebarOpen: false,
          payments: {
            data: rows,
            total: 3,
            from: 1,
            to: 3,
            current_page: 1,
            last_page: 1,
            links: [],
          },
          members: [],
          contributionTypes: [],
          filters: {
            status: "PENDING",
            sort_field: "paid_at",
            sort_direction: "desc",
          },
          canApprovePayments: true,
        },
      };
      await context.route("**/*", async (route) => {
        const request = route.request();
        const pathname = new URL(request.url()).pathname;
        if (pathname.startsWith("/build/")) {
          const file = path.resolve("public", pathname.slice(1));
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
        if (pathname === "/images/logo_kjy2.png")
          return route.fulfill({
            body: await fs.readFile("public/images/logo_kjy2.png"),
            contentType: "image/png",
          });
        if (pathname === "/api/notifications/recent")
          return route.fulfill({
            json: { data: [], meta: { limit: 5, unread_count: 0 } },
          });
        if (/\/payments\/\d+\/proof$/.test(pathname)) {
          const pdf = pathname.includes("/2/");
          return route.fulfill({
            body: await fs.readFile(
              pdf
                ? "tests/Fixtures/manual-transfer-multipage.pdf"
                : "public/images/logo_kjy2.png",
            ),
            headers: {
              "Content-Type": pdf ? "application/pdf" : "image/png",
              "Content-Disposition":
                "inline; filename=synthetic." + (pdf ? "pdf" : "png"),
              "Cache-Control": "private, max-age=0, no-store",
              "X-Content-Type-Options": "nosniff",
              "Content-Security-Policy":
                "sandbox; default-src 'none'; frame-ancestors 'none'",
            },
          });
        }
        if (
          pathname === "/cooperative/payments/1/cancel" &&
          request.method() === "POST"
        ) {
          requests.push(request.postDataJSON());
          rows[0].status = "VOID";
          rows[0].can_cancel = false;
          return route.fulfill({
            json: state,
            headers: { "X-Inertia": "true" },
          });
        }
        if (
          pathname === "/cooperative/payments/2/approve" &&
          request.method() === "POST"
        ) {
          state.props.errors = {
            payment: "Tagihan sudah lunas; periksa pengajuan lain.",
          };
          return route.fulfill({
            json: state,
            headers: { "X-Inertia": "true" },
          });
        }
        if (pathname === "/cooperative/payments") {
          const encoded = JSON.stringify(state)
            .replaceAll("&", "&amp;")
            .replaceAll('"', "&quot;");
          return route.fulfill({
            body: `<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1">${(entry.css ?? []).map((file) => `<link rel="stylesheet" href="/build/${file}">`).join("")}</head><body><div id="app" data-page="${encoded}"></div><script type="module" src="/build/${entry.file}"></script></body></html>`,
            contentType: "text/html",
          });
        }
        errors.push(`Unexpected request ${request.method()} ${pathname}`);
        return route.abort();
      });
      const page = await context.newPage();
      page.on("pageerror", (e) => errors.push(e.message));
      await page.goto(
        "http://payment.test/cooperative/payments?status=PENDING",
      );
      const cancelButtons = page.getByRole("button", {
        name: "Batalkan pengajuan",
        exact: true,
      });
      await cancelButtons.first().waitFor();
      assert.equal(await cancelButtons.count(), 2);
      const approveButton = page
        .getByRole("button", { name: "Approve pembayaran", exact: true })
        .first();
      const approveSize = await approveButton.boundingBox();
      const cancelSize = await cancelButtons.first().boundingBox();
      assert.ok(approveSize && cancelSize);
      assert.equal(Math.round(cancelSize.width), Math.round(approveSize.width));
      assert.equal(
        Math.round(cancelSize.height),
        Math.round(approveSize.height),
      );
      if (viewport.width >= 640) {
        assert.equal(cancelSize.width, 32);
        assert.equal(cancelSize.height, 32);
        assert.equal((await cancelButtons.first().textContent()).trim(), "");
        assert.equal(
          await cancelButtons.first().getAttribute("title"),
          "Batalkan pengajuan",
        );
      }
      const proof = page
        .getByRole("link", { name: "Lihat Bukti Transfer" })
        .first();
      assert.equal(await proof.getAttribute("target"), "_blank");
      const popupPromise = page.waitForEvent("popup");
      await proof.click();
      const popup = await popupPromise;
      await popup.waitForLoadState();
      assert.ok(popup.url().endsWith("/payments/1/proof"));
      assert.equal(
        await popup.locator("img").evaluate((img) => img.naturalWidth > 0),
        true,
      );
      await popup.close();
      await cancelButtons.first().click();
      const dialog = page.getByRole("dialog");
      await dialog.waitFor();
      const submit = dialog.getByRole("button", {
        name: "Batalkan pengajuan",
        exact: true,
      });
      assert.equal(await submit.isDisabled(), true);
      await dialog
        .getByLabel("Alasan pembatalan")
        .fill("Duplikat transfer sintetis");
      await submit.click();
      await dialog.waitFor({ state: "hidden" });
      assert.deepEqual(requests, [{ reason: "Duplikat transfer sintetis" }]);
      assert.equal(await cancelButtons.count(), 1);
      await page
        .getByRole("button", { name: "Approve pembayaran", exact: true })
        .first()
        .click();
      await page
        .getByRole("alert")
        .filter({ hasText: "Tagihan sudah lunas" })
        .waitFor();
      assert.ok(
        await page.evaluate(
          () => document.documentElement.scrollWidth <= innerWidth + 1,
        ),
      );
      assert.deepEqual(errors, []);
      await context.close();
    }
  } finally {
    await browser.close();
  }
});
