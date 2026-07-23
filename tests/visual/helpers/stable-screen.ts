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
`;

export type StableScreenOptions = {
    readyLocator?: Locator;
    screenId?: string;
};

export async function installStableEnvironment(page: Page): Promise<void> {
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
    });

    const loading = page.locator('[aria-busy="true"], [data-loading="true"], [data-inertia-loading="true"]');
    await loading.first().waitFor({ state: "hidden", timeout: 10_000 }).catch(() => undefined);

    await page.waitForFunction(() => Array.from(document.images).every((image) => image.complete), null, {
        timeout: 10_000,
        polling: 100,
    }).catch(() => {
        throw new Error(`Images did not settle for ${screenId}.`);
    });

    if (options.readyLocator) {
        await options.readyLocator.waitFor({ state: "visible", timeout: 15_000 }).catch(() => {
            throw new Error(`Ready locator did not become visible for ${screenId}.`);
        });
    } else {
        await page.getByRole("heading").first().waitFor({ state: "visible", timeout: 15_000 }).catch(() => {
            throw new Error(`No page heading became visible for ${screenId}.`);
        });
    }

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
