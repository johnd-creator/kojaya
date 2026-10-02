import { test as setup } from "@playwright/test";
import { createRoleStorageState } from "./role-setup";

setup("authenticate as anggota", async ({ page }) => {
    await createRoleStorageState(page, "Anggota", "ui.anggota@kojaya.test", "anggota");
});

setup("authenticate as pending anggota", async ({ page }) => {
    await createRoleStorageState(page, "Anggota", "ui.anggota.pending@kojaya.test", "anggota-pending");
});
