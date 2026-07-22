import { test as setup } from "@playwright/test";
import { createRoleStorageState } from "./role-setup";

setup("authenticate as system admin", async ({ page }) => {
    await createRoleStorageState(page, "System Admin", "ui.system@kojaya.test", "system-admin");
});
