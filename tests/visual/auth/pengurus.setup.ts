import { test as setup } from "@playwright/test";
import { createRoleStorageState } from "./role-setup";

setup("authenticate as pengurus", async ({ page }) => {
    await createRoleStorageState(page, "Pengurus Koperasi", "ui.pengurus@kojaya.test", "pengurus");
});
