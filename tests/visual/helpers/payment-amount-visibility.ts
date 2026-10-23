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
  const values = page.locator(
    '[data-testid="payment-amount"]:visible, [data-testid="payment-method"]:visible, [data-testid="payment-status"]:visible',
  );
  for (let index = 0; index < (await values.count()); index++) {
    const amount = values.nth(index);
    await amount.scrollIntoViewIfNeeded();
    const metrics = await amount.evaluate((element) => {
      const range = document.createRange();
      range.selectNodeContents(element);
      const text = range.getBoundingClientRect();
      const cell =
        element.closest("td") ?? element.closest("article") ?? element;
      const bounds = cell.getBoundingClientRect();
      const labelBounds = element.getBoundingClientRect();
      return {
        value: element.textContent?.trim(),
        isAmount: element.getAttribute("data-testid") === "payment-amount",
        left: text.left,
        right: text.right,
        top: text.top,
        bottom: text.bottom,
        cellLeft: Math.max(bounds.left, labelBounds.left),
        cellRight: Math.min(bounds.right, labelBounds.right),
        viewportWidth: innerWidth,
        viewportHeight: innerHeight,
      };
    });
    if (metrics.isAmount) {
      expect(metrics.value).toMatch(/^Rp\s*[\d.,]+$/);
    } else {
      expect(metrics.value).toBeTruthy();
    }
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
