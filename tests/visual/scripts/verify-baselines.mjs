import { readdirSync, readFileSync } from "node:fs";
import { pathToFileURL } from "node:url";
import { resolve } from "node:path";

export const viewportSizes = Object.freeze({
    desktop: { width: 1440, minHeight: 900 },
    tablet: { width: 768, minHeight: 1024 },
    mobile: { width: 390, minHeight: 844 },
});

const pngSignature = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]);

/**
 * @param {Buffer} bytes
 * @returns {{width: number, height: number}}
 */
export function readPngDimensions(bytes) {
    if (bytes.length < pngSignature.length || !bytes.subarray(0, pngSignature.length).equals(pngSignature)) {
        throw new Error("Invalid PNG signature.");
    }

    let offset = pngSignature.length;
    let ihdr = null;
    let ended = false;

    while (offset < bytes.length) {
        if (bytes.length - offset < 12) {
            throw new Error("Truncated PNG chunk header.");
        }

        const length = bytes.readUInt32BE(offset);
        const type = bytes.toString("ascii", offset + 4, offset + 8);
        const dataStart = offset + 8;
        const dataEnd = dataStart + length;
        const crcEnd = dataEnd + 4;

        if (!/^[A-Za-z]{4}$/.test(type)) {
            throw new Error(`Malformed PNG chunk type: ${type || "unknown"}.`);
        }

        if (crcEnd > bytes.length) {
            throw new Error(`Truncated PNG ${type} chunk.`);
        }

        if (offset === pngSignature.length && type !== "IHDR") {
            throw new Error("PNG is missing its first IHDR chunk.");
        }

        if (type === "IHDR") {
            if (ihdr !== null) {
                throw new Error("PNG contains duplicate IHDR chunks.");
            }

            if (length !== 13) {
                throw new Error(`Malformed IHDR chunk length: ${length}.`);
            }

            ihdr = {
                width: bytes.readUInt32BE(dataStart),
                height: bytes.readUInt32BE(dataStart + 4),
            };
        }

        if (type === "IEND") {
            if (length !== 0) {
                throw new Error("Malformed IEND chunk.");
            }

            ended = true;
            break;
        }

        offset = crcEnd;
    }

    if (ihdr === null) {
        throw new Error("PNG is missing its IHDR chunk.");
    }

    if (!ended) {
        throw new Error("PNG is missing its IEND chunk or is truncated.");
    }

    return ihdr;
}

/**
 * @param {string} filePath
 * @param {string} project
 * @returns {{file: string, project: string, actual: {width: number, height: number} | null, expected: {width: number, minHeight: number} | null, reason: string} | null}
 */
export function validateBaselineDimensions(filePath, project) {
    const expected = viewportSizes[project] ?? null;
    let actual = null;

    try {
        actual = readPngDimensions(readFileSync(filePath));
    } catch (error) {
        return {
            file: filePath,
            project,
            actual,
            expected,
            reason: error instanceof Error ? error.message : "Unable to read PNG.",
        };
    }

    if (expected === null) {
        return {
            file: filePath,
            project,
            actual,
            expected,
            reason: `Unknown viewport project: ${project}.`,
        };
    }

    if (actual.width <= 0 || actual.height <= 0) {
        return {
            file: filePath,
            project,
            actual,
            expected,
            reason: `Invalid PNG dimensions: actual ${actual.width} × ${actual.height}; expected width ${expected.width} and minimum height ${expected.minHeight}.`,
        };
    }

    if (actual.width !== expected.width || actual.height < expected.minHeight) {
        return {
            file: filePath,
            project,
            actual,
            expected,
            reason: `Invalid PNG geometry: actual ${actual.width} × ${actual.height}; expected width ${expected.width} and minimum height ${expected.minHeight}.`,
        };
    }

    return null;
}

/**
 * @param {{registryPath?: string, baselineRoot?: string}} options
 */
export function verifyBaselines({
    registryPath = resolve("tests/visual/coverage/cooperative-pages.json"),
    baselineRoot = resolve("tests/visual/baselines"),
} = {}) {
    const registry = JSON.parse(readFileSync(registryPath, "utf8"));
    const expected = new Set();
    const duplicateNames = [];
    const registryNames = new Set();

    for (const entry of registry.entries) {
        if (!entry.visual) {
            continue;
        }

        const name = `${entry.module}--${entry.screen}--${entry.state}.png`;
        if (registryNames.has(name)) {
            duplicateNames.push(name);
        }
        registryNames.add(name);

        for (const project of entry.viewport_policy) {
            expected.add(`${project}/${name}`);
        }
    }

    const actual = new Set();
    let projectDirectories = [];
    try {
        projectDirectories = readdirSync(baselineRoot, { withFileTypes: true });
    } catch (error) {
        if (error?.code !== "ENOENT") {
            throw error;
        }
    }

    for (const projectDirectory of projectDirectories) {
        if (!projectDirectory.isDirectory()) {
            continue;
        }

        const project = projectDirectory.name;
        const directory = resolve(baselineRoot, project);
        for (const file of readdirSync(directory, { withFileTypes: true })) {
            if (file.isFile() && file.name.endsWith(".png")) {
                actual.add(`${project}/${file.name}`);
            }
        }
    }

    const missing = [...expected].filter((file) => !actual.has(file));
    const orphan = [...actual].filter((file) => !expected.has(file));
    const invalidDimensions = [];

    for (const file of actual) {
        const [project] = file.split("/", 1);
        const invalid = validateBaselineDimensions(resolve(baselineRoot, file), project);
        if (invalid !== null) {
            invalidDimensions.push({
                ...invalid,
                file,
            });
        }
    }

    return {
        baselineFiles: actual.size,
        missing,
        orphan,
        duplicateNames,
        invalidDimensions,
        valid: actual.size - invalidDimensions.length,
    };
}

function runCli() {
    const result = verifyBaselines();
    const summary = {
        baseline_files: result.baselineFiles,
        missing_baselines: result.missing.length,
        orphan_baselines: result.orphan.length,
        duplicate_names: result.duplicateNames.length,
        invalid_dimensions: result.invalidDimensions.length,
        valid: result.valid,
    };

    console.log(JSON.stringify(summary, null, 2));
    if (result.missing.length || result.orphan.length || result.duplicateNames.length || result.invalidDimensions.length) {
        console.error(JSON.stringify(result, null, 2));
        process.exitCode = 1;
    }
}

if (process.argv[1] && import.meta.url === pathToFileURL(resolve(process.argv[1])).href) {
    runCli();
}
