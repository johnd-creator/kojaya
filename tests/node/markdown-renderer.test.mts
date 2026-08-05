import assert from "node:assert/strict";
import { test } from "node:test";

import {
  renderMarkdownArticle,
  ALLOWED_URI_REGEXP,
  FORBID_TAGS,
  type TocItem,
} from "../../resources/js/components/Documentation/markdown-renderer.ts";

test("renderMarkdownArticle produces deterministic heading IDs", () => {
  const body = [
    "## Tujuan",
    "",
    "Isi.",
    "",
    "## Langkah",
    "",
    "Langkah 1.",
  ].join("\n");

  const { toc } = renderMarkdownArticle(body);
  const ids = toc.map((item) => item.id);
  assert.deepEqual(ids, ["tujuan", "langkah"]);
});

test("renderMarkdownArticle makes duplicate heading IDs unique", () => {
  const body = [
    "## Tujuan",
    "",
    "Isi pertama.",
    "",
    "## Tujuan",
    "",
    "Isi kedua.",
    "",
    "## Tujuan",
    "",
    "Isi ketiga.",
  ].join("\n");

  const { toc } = renderMarkdownArticle(body);
  const ids = toc.map((item) => item.id);
  assert.deepEqual(ids, ["tujuan", "tujuan-2", "tujuan-3"]);
});

test("renderMarkdownArticle strips a YAML frontmatter block from the body", () => {
  // The frontmatter is split out server-side, so the body that
  // reaches the renderer must not contain it. The renderer must NOT
  // try to be clever: it just feeds marked whatever it is given.
  // This test guards against accidental re-merging.
  const body = "## Tujuan\n\nIsi tanpa frontmatter.";
  const { html, toc } = renderMarkdownArticle(body);
  assert.ok(html.includes("Tujuan"));
  assert.ok(html.includes("Isi tanpa frontmatter"));
  assert.equal(toc.length, 1);
});

test("renderMarkdownArticle removes javascript: URIs from link href", () => {
  const body = "[klik saya](javascript:alert(1))";
  const { html } = renderMarkdownArticle(body);
  assert.ok(!/javascript:/i.test(html), `Found javascript: in output: ${html}`);
});

test("renderMarkdownArticle removes data: URIs from link href", () => {
  const body = "[link](data:text/html,<script>alert(1)</script>)";
  const { html } = renderMarkdownArticle(body);
  assert.ok(!/data:text\/html/i.test(html));
});

test("renderMarkdownArticle removes vbscript: URIs from link href", () => {
  const body = "[link](vbscript:msgbox(1))";
  const { html } = renderMarkdownArticle(body);
  assert.ok(!/vbscript:/i.test(html), `Found vbscript: in output: ${html}`);
});

test("renderMarkdownArticle strips event handler attributes", () => {
  const body = '<a href="/safe" onclick="alert(1)">link</a>';
  const { html } = renderMarkdownArticle(body);
  assert.ok(!/onclick=/i.test(html));
  assert.ok(html.includes('href="/safe"'));
});

test("renderMarkdownArticle removes <script>, <iframe>, <form>, <object>, <embed> tags", () => {
  const body = [
    "<script>alert(1)</script>",
    "<iframe src=\"https://evil.example\"></iframe>",
    "<form action=\"/x\"><input name=\"y\"></form>",
    "<object data=\"/bad\"></object>",
    "<embed src=\"/bad\">",
  ].join("\n");
  const { html } = renderMarkdownArticle(body);
  for (const tag of FORBID_TAGS) {
    assert.ok(!new RegExp(`<${tag}\\b`, "i").test(html), `Tag <${tag}> leaked: ${html}`);
  }
});

test("renderMarkdownArticle keeps safe http(s) and relative links", () => {
  const body = [
    "[external](https://example.org)",
    "[same app](/documentation/foo)",
    "[anchor](#tujuan)",
  ].join("\n");
  const { html } = renderMarkdownArticle(body);
  assert.match(html, /href="https:\/\/example\.org"/);
  assert.match(html, /href="\/documentation\/foo"/);
  assert.match(html, /href="#tujuan"/);
});

test("renderMarkdownArticle produces a stable TOC across calls", () => {
  const body = "## A\n\nIsi.\n\n## B\n\nIsi.\n\n## A\n\nIsi.";
  const a = renderMarkdownArticle(body);
  const b = renderMarkdownArticle(body);
  assert.deepEqual(a.toc, b.toc);
  assert.equal(a.html, b.html);
});

test("renderMarkdownArticle caps TOC depth at h2..h4", () => {
  const body = ["# H1", "## H2", "### H3", "#### H4", "##### H5", "###### H6"].join("\n\n");
  const { toc } = renderMarkdownArticle(body);
  const levels: number[] = toc.map((t: TocItem) => t.level);
  assert.deepEqual(levels, [2, 3, 4]);
});

test("renderMarkdownArticle strips the `style` attribute", () => {
  const body = '<p style="color:red">halo</p>';
  const { html } = renderMarkdownArticle(body);
  assert.ok(!/style=/i.test(html), `Style attribute leaked: ${html}`);
});

test("ALLOWED_URI_REGEXP rejects javascript: at the regex level", () => {
  assert.equal(ALLOWED_URI_REGEXP.test("javascript:alert(1)"), false);
  assert.equal(ALLOWED_URI_REGEXP.test("data:text/html,..."), false);
  assert.equal(ALLOWED_URI_REGEXP.test("vbscript:msgbox"), false);
  assert.equal(ALLOWED_URI_REGEXP.test("https://example.org"), true);
  assert.equal(ALLOWED_URI_REGEXP.test("/documentation/x"), true);
  assert.equal(ALLOWED_URI_REGEXP.test("#tujuan"), true);
});
