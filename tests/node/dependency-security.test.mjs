import assert from "node:assert/strict";
import { EventEmitter } from "node:events";
import { createServer as createHttpServer } from "node:http";
import { readFileSync } from "node:fs";
import { createRequire } from "node:module";
import path from "node:path";
import test from "node:test";
import { fileURLToPath } from "node:url";
import vm from "node:vm";
import { createServer as createViteServer } from "vite";

const require = createRequire(import.meta.url);
const redirects = require("follow-redirects");
const root = fileURLToPath(new URL("../../", import.meta.url));

async function listen(server) {
  await new Promise((resolve, reject) => {
    server.once("error", reject);
    server.listen(0, "127.0.0.1", resolve);
  });
  return `http://127.0.0.1:${server.address().port}`;
}

async function close(server) {
  server.closeAllConnections();
  await new Promise((resolve) => server.close(resolve));
}

function getHeaders(url, headers, sensitiveHeaders = []) {
  return new Promise((resolve, reject) => {
    const request = redirects.http.get(
      url,
      { headers, sensitiveHeaders, agent: false },
      (response) => {
        const chunks = [];
        response.on("data", (chunk) => chunks.push(chunk));
        response.on("end", () => {
          try {
            resolve(JSON.parse(Buffer.concat(chunks).toString()));
          } catch (error) {
            reject(error);
          }
        });
        response.on("error", reject);
      },
    );
    request.setTimeout(3000, () =>
      request.destroy(new Error("Synthetic redirect timeout")),
    );
    request.on("error", reject);
  });
}

test(
  "redirects strip synthetic credentials across origins and retain ordinary headers",
  { timeout: 15000 },
  async (t) => {
    const target = createHttpServer((request, response) =>
      response.end(JSON.stringify(request.headers)),
    );
    let targetOrigin;
    const source = createHttpServer((request, response) => {
      if (request.url === "/same-target")
        return response.end(JSON.stringify(request.headers));
      response.writeHead(302, {
        location:
          request.url === "/same" ? "/same-target" : `${targetOrigin}/target`,
      });
      response.end();
    });
    try {
      targetOrigin = await listen(target);
      const sourceOrigin = await listen(source);
      for (const representation of ["object", "array"]) {
        await t.test(representation, async () => {
          const entries = [
            "Host",
            new URL(sourceOrigin).host,
            "Authorization",
            "Bearer synthetic",
            "Cookie",
            "session=synthetic",
            "Proxy-Authorization",
            "Basic synthetic",
            "X-Public",
            "retained",
          ];
          const headers =
            representation === "array"
              ? entries
              : Object.fromEntries(
                  Array.from({ length: entries.length / 2 }, (_, i) =>
                    entries.slice(i * 2, i * 2 + 2),
                  ),
                );
          const result = await getHeaders(`${sourceOrigin}/cross`, headers);
          for (const name of ["authorization", "cookie", "proxy-authorization"])
            assert.equal(
              result[name],
              undefined,
              `${representation}: ${name} leaked`,
            );
          assert.equal(result["x-public"], "retained");
        });
      }
      for (const name of ["X^Token", "X|Token"]) {
        await t.test(`custom ${name}`, async () => {
          const result = await getHeaders(
            `${sourceOrigin}/cross`,
            { [name]: "synthetic-private", "X-Public": "retained" },
            [name],
          );
          assert.equal(result[name.toLowerCase()], undefined);
          assert.equal(result["x-public"], "retained");
        });
      }
      await t.test("same origin retains credentials", async () => {
        const result = await getHeaders(`${sourceOrigin}/same`, {
          Authorization: "Bearer synthetic",
          Cookie: "session=synthetic",
        });
        assert.equal(result.authorization, "Bearer synthetic");
        assert.equal(result.cookie, "session=synthetic");
      });
    } finally {
      await close(source);
      await close(target);
    }
  },
);

function launcher(platform = "win32", editor = "synthetic-editor") {
  const bundle = readFileSync(
    new URL(
      "../../node_modules/vite/dist/node/chunks/config.js",
      import.meta.url,
    ),
    "utf8",
  );
  const launcherStart = bundle.indexOf("var require_launch_editor =");
  const start = Math.min(
    ...[
      "var require_get_args =",
      "var require_construct_windows_launch_command =",
      "var require_parse_file =",
      "var require_launch_editor =",
    ]
      .map((name) => bundle.indexOf(name))
      .filter((index) => index >= 0),
  );
  const end = bundle.indexOf("//#endregion", launcherStart);
  assert(
    start >= 0 && end > start,
    "Installed Vite launcher boundary must be identifiable",
  );
  const calls = [];
  const checks = [];
  const paths =
    platform === "win32"
      ? path.win32
      : {
          ...path.posix,
          resolve: (file) => path.posix.resolve("/mnt/synthetic", file),
          relative: (_from, file) =>
            path.posix.relative("/mnt/synthetic", file),
        };
  const child = () => Object.assign(new EventEmitter(), { kill() {} });
  const dependencies = {
    fs: {
      existsSync: (file) => {
        checks.push(file);
        return file.length > 0 && !paths.basename(file).includes("missing");
      },
      statSync: () => ({ isFile: () => true }),
    },
    os: {
      release: () =>
        platform === "linux" ? "Microsoft synthetic WSL" : "synthetic",
    },
    path: paths,
    url: { fileURLToPath },
    child_process: {
      exec(command, options) {
        calls.push({ command, options });
        return child();
      },
      spawn(command, args, options) {
        calls.push({ command, args, options });
        return child();
      },
    },
  };
  const context = {
    __commonJSMin: (factory) => () => {
      const module = { exports: {} };
      factory(module.exports, module);
      return module.exports;
    },
    __require: (name) => {
      assert(name in dependencies, `Unexpected launcher dependency: ${name}`);
      return dependencies[name];
    },
    require_picocolors: () => ({ red: (value) => value }),
    require_guess: () => () => [editor],
    process: { platform, env: {}, cwd: () => "/mnt/synthetic" },
    console: { log() {} },
  };
  const launch = vm.runInNewContext(
    `${bundle.slice(start, end)}\nrequire_launch_editor();`,
    context,
    { timeout: 1000 },
  );
  return { launch, calls, checks };
}

test("Vite launcher rejects Windows percent paths and resolves before checking existence without executing processes", () => {
  for (const file of [
    "C:\\synthetic\\proof%SYNTHETIC%.vue",
    "file:///C:/synthetic/proof%25SYNTHETIC%25.vue",
  ]) {
    const { launch, calls } = launcher();
    launch(file, "synthetic-editor", () => {});
    assert.equal(
      calls.length,
      0,
      "Percent paths must never reach an editor command",
    );
  }
  const missing = launcher();
  missing.launch(
    "C:\\synthetic\\missing\\..:1:1",
    "synthetic-editor",
    () => {},
  );
  assert.equal(
    missing.checks[0],
    "C:\\synthetic",
    "Existence must be checked against the resolved identity",
  );
  assert.doesNotMatch(missing.calls[0]?.command ?? "", /missing/);
  const ordinary = launcher();
  ordinary.launch(
    "C:\\synthetic\\proof.vue:12:4",
    "synthetic-editor",
    () => {},
  );
  assert.equal(
    ordinary.calls.length,
    1,
    "Ordinary source locations must still reach the mocked editor",
  );
});

test("Vite Windows editor preserves escaping for an ordinary metacharacter filename", () => {
  for (const character of ["&", "|", "^"]) {
    const { launch, calls } = launcher();
    launch(
      `C:\\synthetic\\proof${character}copy.vue`,
      "synthetic-editor",
      () => {},
    );
    assert.equal(calls.length, 1);
    assert(calls[0].command.includes(`^${character}`));
    assert.doesNotMatch(calls[0].command, /(?<!\^)[&|]/);
  }
});
test("Vite WSL editor receives a filename rather than an editor flag", () => {
  const { launch, calls } = launcher("linux", "vim");
  launch("/mnt/synthetic/+synthetic.vue", "vim", () => {});
  assert.equal(calls.length, 1);
  assert.equal(calls[0].args[0].startsWith("+"), false);
  assert.equal(calls[0].args[0].startsWith("-"), false);
});

test(
  "Vite transforms synthetic HTML and records resolved module identities safely",
  { timeout: 15000 },
  async (t) => {
    const normalizedRoot = root.replaceAll("\\", "/").replace(/\/$/, "");
    const entry = `${normalizedRoot}/__dependency_security_entry__.js`;
    const browserModule = "/__dependency_security_virtual__/marker.js";
    const resolvedModule = `${normalizedRoot}${browserModule}`;
    const vite = await createViteServer({
      root,
      configFile: false,
      envFile: false,
      publicDir: false,
      logLevel: "silent",
      appType: "custom",
      server: { middlewareMode: true, hmr: false, watch: null },
      optimizeDeps: { noDiscovery: true, include: [] },
      plugins: [
        {
          name: "synthetic-security-fixture",
          resolveId(source) {
            if (source === "/__dependency_security_entry__.js") return entry;
            if (source === browserModule) return resolvedModule;
          },
          load(id) {
            if (id === entry)
              return `import { marker } from '${browserModule}'; globalThis.__syntheticMarker = marker;`;
            if (id === resolvedModule)
              return 'export const marker = "synthetic";';
          },
        },
      ],
    });
    try {
      await t.test("ordinary inline module remains supported", async () => {
        const html = await vite.transformIndexHtml(
          "/index.html",
          '<html><body><script type="module">globalThis.__synthetic = true;</script></body></html>',
        );
        assert.match(html, /html-proxy/);
        assert.doesNotMatch(html, /attacker\.invalid/);
      });
      await t.test(
        "protocol-relative query traversal cannot become an external module source",
        async () => {
          const html = await vite.transformIndexHtml(
            "//attacker.invalid/synthetic.js?/../../package.json",
            '<html><body><script type="module">globalThis.__synthetic = true;</script></body></html>',
          );
          assert.doesNotMatch(html, /src=["']\/\/attacker\.invalid/);
        },
      );
      await t.test(
        "import allowlisting uses resolved filesystem identity",
        async () => {
          const transformed = await vite.environments.client.transformRequest(
            "/__dependency_security_entry__.js",
          );
          assert.match(transformed.code, /__syntheticMarker/);
          assert.equal(vite.config.safeModulePaths.has(resolvedModule), true);
          assert.equal(vite.config.safeModulePaths.has(browserModule), false);
        },
      );
    } finally {
      await vite.close();
    }
  },
);
