import { test as setup } from "@playwright/test";
import { createRoleStorageState } from "./role-setup";

setup("authenticate as manajer", async ({ page }) => {
    await createRoleStorageState(page, "Manajer Koperasi", "ui.manajer@kojaya.test", "manajer");
});
