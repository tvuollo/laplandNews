import type { NewsApiResponse, NewsQuery } from "../types/news";
import { assertIsNewsApiResponse } from "../lib/validate";

const DEFAULT_BASE_URL = "";
// "" means same-origin (recommended if your React is hosted on same domain as PHP).
// If not, set e.g. "https://yourdomain.com"

function buildQuery(params: Record<string, string | undefined>): string {
    const usp = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
        if (v && v.trim() !== "") usp.set(k, v);
    }
    const s = usp.toString();
    return s ? `?${s}` : "";
}

export async function fetchNews(query: NewsQuery = {}, baseUrl = DEFAULT_BASE_URL): Promise<NewsApiResponse> {
    const qs = buildQuery({
        bucket: query.bucket,
        source: query.source,
    });

    const res = await fetch(`${baseUrl}/api/news.php${qs}`, {
        headers: { Accept: "application/json" },
    });

    if (!res.ok) {
        const text = await res.text().catch(() => "");
        throw new Error(`News API failed: ${res.status} ${res.statusText} ${text}`);
    }

    const json: unknown = await res.json();
    assertIsNewsApiResponse(json);
    return json;
}