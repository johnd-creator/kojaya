import fs from "node:fs/promises";
import path from "node:path";
import type { Locator, Page } from "@playwright/test";

const fixedNow = process.env.UI_AUDIT_FIXED_NOW ?? "2026-01-15T09:30:00+07:00";

const stabilityStyles = `
    *, *::before, *::after {
        animation-duration: 0s !important;
        animation-delay: 0s !important;
        transition-duration: 0s !important;
        transition-delay: 0s !important;
        caret-color: transparent !important;
    }
    html { scroll-behavior: auto !important; }
    img[alt="KojayaPro"] + div > span:first-child {
        text-rendering: geometricPrecision !important;
    }
`;

const deterministicFontWeights = ["400", "500", "600"] as const;
const deterministicFontCss = deterministicFontWeights.map((weight) => `
    @font-face {
        font-family: "Instrument Sans";
        font-style: normal;
        font-weight: ${weight};
        font-display: block;
        src: url(https://fonts.bunny.net/instrument-sans/files/instrument-sans-latin-${weight}-normal.woff2) format("woff2");
    }
`).join("\n");

async function installDeterministicFonts(page: Page): Promise<void> {
    const fontAssets = new Map(
        await Promise.all(deterministicFontWeights.map(async (weight) => [
            weight,
            await fs.readFile(path.resolve(
                "tests/visual/assets",
                `instrument-sans-latin-${weight}-normal.woff2`,
            )),
        ] as const)),
    );

    await page.route(/^https:\/\/fonts\.bunny\.net\/css\?family=instrument-sans:400,500,600(?:&.*)?$/, async (route) => {
        await route.fulfill({
            status: 200,
            contentType: "text/css",
            body: deterministicFontCss,
        });
    });

    await page.route(/^https:\/\/fonts\.bunny\.net\/instrument-sans\/files\/instrument-sans-latin-(400|500|600)-normal\.woff2$/, async (route) => {
        const match = route.request().url().match(/latin-(400|500|600)-normal\.woff2$/);
        const body = match ? fontAssets.get(match[1] as typeof deterministicFontWeights[number]) : undefined;

        if (!body) {
            await route.abort("failed");
            return;
        }

        await route.fulfill({
            status: 200,
            contentType: "font/woff2",
            body,
        });
    });
}

export type StableScreenOptions = {
    readyLocator?: Locator;
    screenId?: string;
};

async function waitForExpectedContent(page: Page, screenId: string): Promise<void> {
    const expectedContent = new Map<string, Locator>([
        ["loans-create-default", page.getByRole("button", { name: "Simpan Pengajuan" })],
        ["operator-dashboard-default", page.getByText("Pengecualian & Anomali", { exact: true })],
        ["pos-register-default", page.getByText("Beras Audit 5kg", { exact: true })],
        ["pos-inventory-transfers-index-default", page.getByText("Belum ada transfer", { exact: true })],
        ["points-index-default", page.getByText("Daftar Poin Anggota", { exact: true })],
        ["admin-loans-index-default", page.locator('[data-testid="loans-list-card"]')],
        ["admin-loan-types-index-default", page.locator('[data-testid="loan-types-list-card"]')],
        ["admin-points-index-default", page.locator('[data-testid="points-table-card"]')],
        ["store-credit-index-default", page.getByText("UI Audit Positif", { exact: true })],
        ["store-credit-index-empty", page.getByText("Belum ada akun saldo toko.", { exact: true })],
        ["store-credit-index-search-results", page.getByText("UI Audit Positif", { exact: true })],
        ["store-credit-index-open-account-dialog", page.getByRole("dialog")],
        [
            "store-credit-index-validation-error",
            page.getByRole("dialog").locator("p.text-xs.text-red-500").first(),
        ],
    ]);
    const locator = expectedContent.get(screenId);

    if (locator) {
        await locator.waitFor({ state: "visible", timeout: 15_000 }).catch(() => {
            throw new Error(`Expected content did not become visible for ${screenId}.`);
        });
    }

    if (screenId.includes("store-credit-index-open-account-dialog") || screenId.includes("store-credit-index-validation-error")) {
        await page.waitForFunction(() => {
            return document.querySelector('[data-slot="dialog-overlay"]')?.getAttribute("data-state") === "open";
        }, null, { timeout: 15_000, polling: 100 }).catch(() => {
            throw new Error(`Dialog overlay did not reach the open state for ${screenId}.`);
        });
    }

    if (screenId !== "pos-transactions-receipt-default") {
        await page.waitForFunction(() => {
            const mainContent = document.querySelector("#main-content");

            return Boolean(mainContent && (mainContent.textContent?.trim().length ?? 0) > 20);
        }, null, { timeout: 15_000, polling: 100 }).catch(() => {
            throw new Error(`Main content did not render for ${screenId}.`);
        });
    }

    await page.evaluate(() => new Promise<void>((resolve) => {
        let frames = 0;
        const waitForNextFrame = (): void => {
            frames += 1;

            if (frames >= 4) {
                resolve();
                return;
            }

            requestAnimationFrame(waitForNextFrame);
        };

        requestAnimationFrame(waitForNextFrame);
    }));
}

export async function installStableEnvironment(page: Page): Promise<void> {
    await installDeterministicFonts(page);

    await page.addInitScript(({ now, css }: { now: string; css: string }) => {
        const RealDate = Date;
        const fixedTimestamp = new RealDate(now).getTime();

        class FrozenDate extends RealDate {
            constructor(...args: ConstructorParameters<typeof Date>) {
                if (args.length === 0) {
                    super(fixedTimestamp);
                } else {
                    super(...args);
                }
            }

            static now(): number {
                return fixedTimestamp;
            }
        }

        window.Date = FrozenDate;

        const installStyles = (): void => {
            if (document.querySelector("[data-ui-audit-stability]")) {
                return;
            }

            const style = document.createElement("style");
            style.dataset.uiAuditStability = "true";
            style.textContent = css;
            document.head.appendChild(style);
        };

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", installStyles, { once: true });
        } else {
            installStyles();
        }
    }, { now: fixedNow, css: stabilityStyles });

    await page.emulateMedia({ reducedMotion: "reduce" });
}

export async function waitForStableScreen(
    page: Page,
    options: StableScreenOptions = {},
): Promise<void> {
    const screenId = options.screenId ?? "unknown-screen";

    await page.waitForLoadState("domcontentloaded");
    await page.evaluate(async () => {
        await document.fonts.ready;
        await Promise.all([
            document.fonts.load('400 16px "Instrument Sans"'),
            document.fonts.load('500 16px "Instrument Sans"'),
            document.fonts.load('600 16px "Instrument Sans"'),
        ]);
    });

    const loading = page.locator('[aria-busy="true"], [data-loading="true"], [data-inertia-loading="true"]');
    await loading.first().waitFor({ state: "hidden", timeout: 10_000 }).catch(() => undefined);

    await page.waitForFunction(() => Array.from(document.images).every((image) => image.complete), null, {
        timeout: 10_000,
        polling: 100,
    }).catch(() => {
        throw new Error(`Images did not settle for ${screenId}.`);
    });

    await page.evaluate(async () => {
        await Promise.all(Array.from(document.images).map(async (image) => {
            if (typeof image.decode !== "function") {
                return;
            }

            try {
                await image.decode();
            } catch {
                // Broken images are reported by runtime health; do not hide them here.
            }
        }));
    });

    if (options.readyLocator) {
        await options.readyLocator.waitFor({ state: "visible", timeout: 15_000 }).catch(() => {
            throw new Error(`Ready locator did not become visible for ${screenId}.`);
        });
    } else {
        await page.locator(
            "#main-content h1, #main-content h2, #main-content h3, #main-content h4, #main-content h5, #main-content h6",
        ).first().waitFor({ state: "visible", timeout: 15_000 }).catch(() => {
            throw new Error(`No main-content heading became visible for ${screenId}.`);
        });
    }

    await page.waitForLoadState("networkidle", { timeout: 15_000 }).catch(() => {
        throw new Error(`Network requests did not settle for ${screenId}.`);
    });

    await page.waitForFunction(() => {
        const currentHeight = document.documentElement.scrollHeight;
        const previousHeight = Number(document.body.dataset.uiAuditHeight ?? "-1");
        const stableMeasurements = Number(document.body.dataset.uiAuditStableMeasurements ?? "0");

        document.body.dataset.uiAuditHeight = String(currentHeight);
        document.body.dataset.uiAuditStableMeasurements = String(
            currentHeight === previousHeight ? stableMeasurements + 1 : 0,
        );

        return Number(document.body.dataset.uiAuditStableMeasurements) >= 2;
    }, null, { timeout: 15_000, polling: 100 }).catch(() => {
        throw new Error(`Document layout did not stabilize for ${screenId}.`);
    });

    const skeletons = await page.locator('[data-slot="skeleton"]').all();
    await Promise.all(skeletons.map((skeleton) => skeleton.waitFor({ state: "hidden", timeout: 15_000 })));
    await waitForExpectedContent(page, screenId);
}

/** @deprecated Use installStableEnvironment before navigation and waitForStableScreen after it. */
export async function prepareStableScreen(page: Page): Promise<void> {
    await installStableEnvironment(page);
}

export async function assertNoHorizontalOverflow(page: Page): Promise<void> {
    const hasHorizontalOverflow = await page.evaluate(() =>
        document.documentElement.scrollWidth > document.documentElement.clientWidth,
    );

    if (hasHorizontalOverflow) {
        throw new Error("Unexpected horizontal overflow detected on the audited screen.");
    }
}
