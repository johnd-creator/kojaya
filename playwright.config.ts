import { defineConfig, devices } from "@playwright/test";
import path from "node:path";
import { fileURLToPath } from "node:url";

const projectRoot = path.dirname(fileURLToPath(import.meta.url));
const authDir = path.join(projectRoot, "tests/visual/.auth");

const authState = (role: string): string => path.join(authDir, `${role}.json`);
const allAuthProjects = [
    "setup-system-admin",
    "setup-pengurus",
    "setup-manajer",
    "setup-admin",
    "setup-kasir",
    "setup-anggota",
];

export default defineConfig({
    testDir: "./tests/visual",
    outputDir: "./test-results",
    snapshotPathTemplate: "{testDir}/baselines/{projectName}/{arg}{ext}",
    globalSetup: "./tests/visual/global-setup.ts",
    globalTeardown: "./tests/visual/global-teardown.ts",
    fullyParallel: false,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 1 : 0,
    workers: process.env.CI ? 2 : 1,
    timeout: 45_000,
    expect: {
        timeout: 10_000,
        toHaveScreenshot: {
            animations: "disabled",
            caret: "hide",
            maxDiffPixelRatio: 0.001,
            threshold: 0.15,
        },
    },
    reporter: [["list"], ["html", { outputFolder: "playwright-report", open: "never" }]],
    use: {
        baseURL: "http://127.0.0.1:18080",
        locale: "id-ID",
        timezoneId: "Asia/Jakarta",
        colorScheme: "light",
        serviceWorkers: "block",
        screenshot: "only-on-failure",
        trace: "retain-on-failure",
        video: "off",
        ignoreHTTPSErrors: true,
        actionTimeout: 10_000,
        navigationTimeout: 30_000,
    },
    webServer: {
        command: "php artisan serve --env=playwright --host=127.0.0.1 --port=18080",
        url: "http://127.0.0.1:18080/login",
        reuseExistingServer: false,
        timeout: 120_000,
        stdout: "pipe",
        stderr: "pipe",
    },
    projects: [
        {
            name: "setup-system-admin",
            testMatch: /auth\/system-admin\.setup\.ts/,
            use: { ...devices["Desktop Chrome"] },
        },
        {
            name: "setup-pengurus",
            testMatch: /auth\/pengurus\.setup\.ts/,
            use: { ...devices["Desktop Chrome"] },
        },
        {
            name: "setup-manajer",
            testMatch: /auth\/manajer\.setup\.ts/,
            use: { ...devices["Desktop Chrome"] },
        },
        {
            name: "setup-admin",
            testMatch: /auth\/admin\.setup\.ts/,
            use: { ...devices["Desktop Chrome"] },
        },
        {
            name: "setup-kasir",
            testMatch: /auth\/kasir\.setup\.ts/,
            use: { ...devices["Desktop Chrome"] },
        },
        {
            name: "setup-anggota",
            testMatch: /auth\/anggota\.setup\.ts/,
            use: { ...devices["Desktop Chrome"] },
        },
        {
            name: "desktop",
            dependencies: allAuthProjects,
            use: {
                ...devices["Desktop Chrome"],
                viewport: { width: 1440, height: 900 },
                storageState: authState("pengurus"),
            },
        },
        {
            name: "tablet",
            dependencies: allAuthProjects,
            use: {
                ...devices["Desktop Chrome"],
                viewport: { width: 768, height: 1024 },
                storageState: authState("pengurus"),
            },
        },
        {
            name: "mobile",
            dependencies: allAuthProjects,
            use: {
                ...devices["Desktop Chrome"],
                viewport: { width: 390, height: 844 },
                storageState: authState("pengurus"),
            },
        },
    ],
});
