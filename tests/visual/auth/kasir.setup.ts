import { test as setup } from "@playwright/test";
import { createRoleStorageState } from "./role-setup";

setup("authenticate as kasir", async ({ page }) => {
    await createRoleStorageState(page, "Kasir Koperasi", "ui.kasir@kojaya.test", "kasir");
});
