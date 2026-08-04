export type CountedQueueItem = {
  count: number;
};

export function actionableQueueItems<T extends CountedQueueItem>(
  items: T[],
): T[] {
  return items.filter((item) => item.count > 0);
}

export function primaryQueueItem<T extends CountedQueueItem>(
  items: T[],
): T | undefined {
  return actionableQueueItems(items)[0];
}
