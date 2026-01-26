export type IsoDateString = string; // DATE_ATOM from PHP: "2026-01-26T07:12:00+00:00"

export interface SourceRef {
    id: string;
    name: string;
}

export interface NewsItem {
    id: string;
    title: string;
    url: string;
    canonicalUrl: string;
    publishedAt: IsoDateString | null;
    categories: string[];
    bucket: string;  // e.g. "lapland"
    lang: string;    // e.g. "fi"
    sources: SourceRef[]; // merged sources
}

export interface NewsApiResponse {
    fetchedAt: IsoDateString;
    ttlSeconds: number;
    sourcesCount: number;
    itemsCount: number;
    errors: Array<{
        sourceId: string;
        message: string;
    }>;
    items: NewsItem[];
}

export interface NewsQuery {
    bucket?: string;
    source?: string;
}