import assert from "node:assert/strict";
import { mkdtempSync, mkdirSync, rmSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";
import { test } from "node:test";
import { deflateSync } from "node:zlib";
import { verifyBaselines } from "./verify-baselines.mjs";

const pngSignature = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]);

function crc32(bytes) {
    let crc = 0xffffffff;
    for (const byte of bytes) {
        crc ^= byte;
        for (let bit = 0; bit < 8; bit += 1) {
            crc = (crc >>> 1) ^ ((crc & 1) ? 0xedb88320 : 0);
        }
    }
    return (crc ^ 0xffffffff) >>> 0;
}

function chunk(type, data) {
    const typeBytes = Buffer.from(type, "ascii");
    const contents = Buffer.concat([typeBytes, data]);
    const checksum = Buffer.alloc(4);
    checksum.writeUInt32BE(crc32(contents));
    const length = Buffer.alloc(4);
    length.writeUInt32BE(data.length);
    return Buffer.concat([length, contents, checksum]);
}

function png(width, height) {
    const header = Buffer.alloc(13);
    header.writeUInt32BE(width, 0);
    header.writeUInt32BE(height, 4);
    header[8] = 8;
    header[9] = 6;
    const row = Buffer.alloc(width * 4 + 1);
    const pixels = Buffer.alloc(row.length * height);
    for (let y = 0; y < height; y += 1) {
        row.copy(pixels, y * row.length);
    }

    return Buffer.concat([
        pngSignature,
        chunk("IHDR", header),
        chunk("IDAT", deflateSync(pixels)),
        chunk("IEND", Buffer.alloc(0)),
    ]);
}

function withFixture(callback) {
    const root = mkdtempSync(join(tmpdir(), "kojaya-baseline-verifier-"));
    const baselineRoot = join(root, "baselines");
    mkdirSync(join(baselineRoot, "desktop"), { recursive: true });
    mkdirSync(join(baselineRoot, "tablet"));
    mkdirSync(join(baselineRoot, "mobile"));
    const registryPath = join(root, "registry.json");

    try {
        callback({ baselineRoot, registryPath });
    } finally {
        rmSync(root, { recursive: true, force: true });
    }
}

function writeRegistry(registryPath, entries) {
    writeFileSync(registryPath, JSON.stringify({
        entries: entries.map(({ name, projects = ["desktop"] }) => ({
            module: "dashboard",
            screen: name,
            state: "default",
            visual: true,
            viewport_policy: projects,
        })),
    }));
}

test("accepts desktop viewport and full-page geometry", () => {
    withFixture(({ baselineRoot, registryPath }) => {
        writeRegistry(registryPath, [{ name: "viewport" }, { name: "full-page" }]);
        writeFileSync(join(baselineRoot, "desktop", "dashboard--viewport--default.png"), png(1440, 900));
        writeFileSync(join(baselineRoot, "desktop", "dashboard--full-page--default.png"), png(1440, 2423));

        assert.deepEqual(verifyBaselines({ registryPath, baselineRoot }), {
            baselineFiles: 2,
            missing: [],
            orphan: [],
            duplicateNames: [],
            invalidDimensions: [],
            valid: 2,
        });
    });
});

test("accepts tablet and mobile full-page geometry", () => {
    withFixture(({ baselineRoot, registryPath }) => {
        writeRegistry(registryPath, [
            { name: "tablet-full-page", projects: ["tablet"] },
            { name: "mobile-full-page", projects: ["mobile"] },
        ]);
        writeFileSync(join(baselineRoot, "tablet", "dashboard--tablet-full-page--default.png"), png(768, 1800));
        writeFileSync(join(baselineRoot, "mobile", "dashboard--mobile-full-page--default.png"), png(390, 1600));

        assert.equal(verifyBaselines({ registryPath, baselineRoot }).invalidDimensions.length, 0);
    });
});

test("rejects desktop and tablet width mismatches", () => {
    withFixture(({ baselineRoot, registryPath }) => {
        writeRegistry(registryPath, [
            { name: "desktop-wrong-width" },
            { name: "tablet-wrong-width", projects: ["tablet"] },
        ]);
        writeFileSync(join(baselineRoot, "desktop", "dashboard--desktop-wrong-width--default.png"), png(1439, 900));
        writeFileSync(join(baselineRoot, "tablet", "dashboard--tablet-wrong-width--default.png"), png(1440, 1800));

        const result = verifyBaselines({ registryPath, baselineRoot });

        assert.equal(result.invalidDimensions.length, 2);
        assert.match(result.invalidDimensions.map((invalid) => invalid.reason).join(" "), /expected width 1440|expected width 768/);
        assert.deepEqual(result.invalidDimensions[0].expected, { width: 1440, minHeight: 900 });
    });
});

test("rejects a mobile baseline shorter than its viewport", () => {
    withFixture(({ baselineRoot, registryPath }) => {
        writeRegistry(registryPath, [{ name: "mobile-short", projects: ["mobile"] }]);
        writeFileSync(join(baselineRoot, "mobile", "dashboard--mobile-short--default.png"), png(390, 843));

        const result = verifyBaselines({ registryPath, baselineRoot });

        assert.equal(result.invalidDimensions.length, 1);
        assert.match(result.invalidDimensions[0].reason, /expected width 390 and minimum height 844/);
    });
});

test("rejects zero PNG dimensions", () => {
    withFixture(({ baselineRoot, registryPath }) => {
        writeRegistry(registryPath, [{ name: "zero-width" }, { name: "zero-height" }]);
        writeFileSync(join(baselineRoot, "desktop", "dashboard--zero-width--default.png"), png(0, 900));
        writeFileSync(join(baselineRoot, "desktop", "dashboard--zero-height--default.png"), png(1440, 0));

        const result = verifyBaselines({ registryPath, baselineRoot });

        assert.equal(result.invalidDimensions.length, 2);
        assert.match(result.invalidDimensions.map((invalid) => invalid.reason).join(" "), /actual 0 × 900|actual 1440 × 0/);
    });
});

test("reports invalid signature and truncated PNGs without throwing", () => {
    withFixture(({ baselineRoot, registryPath }) => {
        writeRegistry(registryPath, [{ name: "malformed" }, { name: "truncated" }]);
        writeFileSync(join(baselineRoot, "desktop", "dashboard--malformed--default.png"), Buffer.from("not-a-png"));
        writeFileSync(join(baselineRoot, "desktop", "dashboard--truncated--default.png"), png(1440, 900).subarray(0, 20));

        const result = verifyBaselines({ registryPath, baselineRoot });

        assert.equal(result.invalidDimensions.length, 2);
        assert.ok(result.invalidDimensions.every((invalid) => invalid.actual === null));
        assert.match(result.invalidDimensions.map((invalid) => invalid.reason).join(" "), /Invalid PNG signature|Truncated PNG/);
    });
});

test("rejects missing and malformed first IHDR chunks", () => {
    withFixture(({ baselineRoot, registryPath }) => {
        writeRegistry(registryPath, [{ name: "missing-ihdr" }, { name: "malformed-ihdr" }]);
        writeFileSync(join(baselineRoot, "desktop", "dashboard--missing-ihdr--default.png"), Buffer.concat([
            pngSignature,
            chunk("IDAT", Buffer.from([0])),
            chunk("IEND", Buffer.alloc(0)),
        ]));
        writeFileSync(join(baselineRoot, "desktop", "dashboard--malformed-ihdr--default.png"), Buffer.concat([
            pngSignature,
            chunk("IHDR", Buffer.alloc(12)),
            chunk("IEND", Buffer.alloc(0)),
        ]));

        const result = verifyBaselines({ registryPath, baselineRoot });

        assert.equal(result.invalidDimensions.length, 2);
        assert.match(result.invalidDimensions.map((invalid) => invalid.reason).join(" "), /first IHDR|Malformed IHDR/);
    });
});

test("rejects an unknown project directory", () => {
    withFixture(({ baselineRoot, registryPath }) => {
        writeRegistry(registryPath, []);
        mkdirSync(join(baselineRoot, "unknown"));
        writeFileSync(join(baselineRoot, "unknown", "dashboard--unknown--default.png"), png(1440, 900));

        const result = verifyBaselines({ registryPath, baselineRoot });

        assert.deepEqual(result.orphan, ["unknown/dashboard--unknown--default.png"]);
        assert.equal(result.invalidDimensions.length, 1);
        assert.match(result.invalidDimensions[0].reason, /Unknown viewport project: unknown/);
    });
});
