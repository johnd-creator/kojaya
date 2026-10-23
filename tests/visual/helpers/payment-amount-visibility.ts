import { expect, type Page } from "@playwright/test";

/** Check text bounds, not only page overflow: compact cells can hide money digits. */
export async function assertPaymentAmountsVisible(
  page: Page,
  allowEmpty = false,
): Promise<void> {
  const amounts = page.locator('[data-testid="payment-amount"]:visible');
  if (allowEmpty) {
    await expect(amounts).toHaveCount(0);
    return;
  }
  await expect(amounts.first()).toBeVisible();
  for (let index = 0; index < (await amounts.count()); index++) {
    const amount = amounts.nth(index);
    await amount.scrollIntoViewIfNeeded();
    const metrics = await amount.evaluate((element) => {
      const range = document.createRange();
      range.selectNodeContents(element);
      const text = range.getBoundingClientRect();
      const cell =
        element.closest("td") ?? element.closest("article") ?? element;
      const bounds = cell.getBoundingClientRect();
      return {
        value: element.textContent?.trim(),
        left: text.left,
        right: text.right,
        top: text.top,
        bottom: text.bottom,
        cellLeft: bounds.left,
        cellRight: bounds.right,
        viewportWidth: innerWidth,
        viewportHeight: innerHeight,
      };
    });
    expect(metrics.value).toMatch(/^Rp\s*[\d.,]+$/);
    expect(
      metrics.left,
      `${metrics.value}: clipped left edge`,
    ).toBeGreaterThanOrEqual(metrics.cellLeft - 1);
    expect(
      metrics.right,
      `${metrics.value}: clipped right edge`,
    ).toBeLessThanOrEqual(metrics.cellRight + 1);
    expect(metrics.left).toBeGreaterThanOrEqual(-1);
    expect(metrics.right).toBeLessThanOrEqual(metrics.viewportWidth + 1);
    expect(metrics.top).toBeGreaterThanOrEqual(-1);
    expect(metrics.bottom).toBeLessThanOrEqual(metrics.viewportHeight + 1);
  }
}
