import type { NewsApiResponse, NewsItem } from "../types/news";

function isObject(v: unknown): v is Record<string, unknown> {
    return typeof v === "object" && v !== null;
}

function isString(v: unknown): v is string {
    return typeof v === "string";
}

function isStringOrNull(v: unknown): v is string | null {
    return v === null || typeof v === "string";
}

function isStringArray(v: unknown): v is string[] {
    return Array.isArray(v) && v.every(isString);
}

function isSourceRefArray(v: unknown): v is Array<{ id: string; name: string }> {
    return (
        Array.isArray(v) &&
        v.every(
            (s) =>
                isObject(s) &&
                isString(s.id) &&
                isString(s.name)
        )
    );
}

function isNewsItem(v: unknown): v is NewsItem {
    return (
        isObject(v) &&
        isString(v.id) &&
        isString(v.title) &&
        isString(v.url) &&
        isString(v.canonicalUrl) &&
        isStringOrNull(v.publishedAt) &&
        isStringArray(v.categories) &&
        isString(v.bucket) &&
        isString(v.lang) &&
        isSourceRefArray(v.sources)
    );
}

export function assertIsNewsApiResponse(v: unknown): asserts v is NewsApiResponse {
    if (!isObject(v)) throw new Error("Response is not an object.");

    const items = v.items;
    if (!Array.isArray(items) || !items.every(isNewsItem)) {
        throw new Error("Response.items is not a valid NewsItem array.");
    }

    if (!isString(v.fetchedAt)) throw new Error("Response.fetchedAt missing/invalid.");
    if (typeof v.ttlSeconds !== "number") throw new Error("Response.ttlSeconds missing/invalid.");
    if (typeof v.sourcesCount !== "number") throw new Error("Response.sourcesCount missing/invalid.");
    if (typeof v.itemsCount !== "number") throw new Error("Response.itemsCount missing/invalid.");
    if (!Array.isArray(v.errors)) throw new Error("Response.errors missing/invalid.");
}