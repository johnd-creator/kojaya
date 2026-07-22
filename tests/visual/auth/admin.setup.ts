import { test as setup } from "@playwright/test";
import { createRoleStorageState } from "./role-setup";

setup("authenticate as admin", async ({ page }) => {
    await createRoleStorageState(page, "Admin Koperasi", "ui.admin@kojaya.test", "admin");
});
