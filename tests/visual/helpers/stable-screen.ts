import type { Page } from "@playwright/test";

const fixedNow = "2026-01-15T09:30:00+07:00";

export async function prepareStableScreen(page: Page): Promise<void> {
    await page.addInitScript(({ now }: { now: string }) => {
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
    }, { now: fixedNow });

    await page.emulateMedia({ reducedMotion: "reduce" });
    await page.addStyleTag({
        content: `
            *, *::before, *::after {
                animation-duration: 0s !important;
                animation-delay: 0s !important;
                transition-duration: 0s !important;
                transition-delay: 0s !important;
                caret-color: transparent !important;
            }
            html { scroll-behavior: auto !important; }
        `,
    });

    await page.waitForLoadState("domcontentloaded");
    await page.evaluate(async () => {
        await document.fonts.ready;
    });
    await page.locator('[aria-busy="true"], [data-loading="true"], [data-inertia-loading="true"]')
        .waitFor({ state: "hidden", timeout: 5_000 })
        .catch(() => undefined);
}

export async function assertNoHorizontalOverflow(page: Page): Promise<void> {
    const hasHorizontalOverflow = await page.evaluate(() =>
        document.documentElement.scrollWidth > document.documentElement.clientWidth,
    );

    if (hasHorizontalOverflow) {
        throw new Error("Unexpected horizontal overflow detected on the audited screen.");
    }
}
