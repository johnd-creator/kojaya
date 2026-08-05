import DOMPurify from "isomorphic-dompurify";
import { marked, type Tokens } from "marked";

export type TocItem = {
  level: number;
  id: string;
  text: string;
};

export type RenderResult = {
  html: string;
  toc: TocItem[];
};

export const ALLOWED_TAGS = [
  "h1", "h2", "h3", "h4", "h5", "h6",
  "p", "br", "hr",
  "ul", "ol", "li",
  "strong", "em", "code", "pre",
  "blockquote",
  "table", "thead", "tbody", "tr", "th", "td",
  "a", "img",
  "figure", "figcaption",
];

export const ALLOWED_ATTR = [
  "href", "title", "rel", "target", "id",
  "src", "alt", "loading", "width", "height",
  "scope",
];

export const FORBID_ATTR = [
  "style", "onerror", "onload", "onclick", "onmouseover", "onfocus", "onblur",
  "onchange", "onsubmit", "onkeydown", "onkeyup", "onkeypress",
];

export const FORBID_TAGS = [
  "script", "iframe", "form", "input", "object", "embed", "base", "meta", "link",
];

// Only http(s), mailto, tel, anchors, and same-app relative paths.
// This deliberately rejects javascript:, data:, vbscript:, file:, etc.
export const ALLOWED_URI_REGEXP = /^(?:https?:|mailto:|tel:|#|\/|\.\/|\.\.\/)/i;

function slugify(text: string): string {
  return text
    .toLowerCase()
    .normalize("NFKD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[^a-z0-9\s-]/g, "")
    .trim()
    .replace(/\s+/g, "-")
    .replace(/-+/g, "-")
    .replace(/^-+|-+$/g, "");
}

function makeUniqueId(base: string, used: Map<string, number>): string {
  if (!used.has(base)) {
    used.set(base, 1);

    return base;
  }
  const next = used.get(base)! + 1;
  used.set(base, next);

  return `${base}-${next}`;
}

export interface RendererState {
  usedIds: Map<string, number>;
  toc: TocItem[];
}

export function makeRendererState(): RendererState {
  return {
    usedIds: new Map<string, number>(),
    toc: [],
  };
}

export function buildRenderer(state: RendererState): marked.Renderer {
  const renderer = new marked.Renderer();

  renderer.heading = function ({ tokens, depth }: Tokens.Heading): string {
    const text = this.parser.parseInline(tokens, this);
    const plain = tokens
      .map((t) => ("text" in t && typeof t.text === "string" ? t.text : ""))
      .join("")
      .trim();
    const id = makeUniqueId(slugify(plain) || `section`, state.usedIds);
    if (depth >= 2 && depth <= 4) {
      state.toc.push({ level: depth, id, text: plain });
    }

    return `<h${depth} id="${id}">${text}</h${depth}>`;
  };

  return renderer;
}

/**
 * Render Markdown to a sanitised HTML string, plus the deterministic
 * table-of-contents entries extracted from headings. The function is
 * pure and stateless at the call-site level: a fresh state is created
 * on every call, so it can be invoked per request without leaking
 * IDs across calls.
 *
 * @param body The raw Markdown body, WITHOUT YAML frontmatter.
 */
export function renderMarkdownArticle(body: string): RenderResult {
  const state = makeRendererState();
  const renderer = buildRenderer(state);

  const raw = marked.parse(body, {
    async: false,
    gfm: true,
    breaks: false,
    renderer,
  }) as string;

  const html = DOMPurify.sanitize(raw, {
    ALLOWED_TAGS,
    ALLOWED_ATTR,
    ALLOWED_URI_REGEXP,
    FORBID_ATTR,
    FORBID_TAGS,
  });

  return { html, toc: state.toc.slice() };
}
