import { useEffect, useMemo, useState } from "react";
import type { NewsApiResponse, NewsQuery } from "../types/news";
import { fetchNews } from "../api/newsApi";

export function useNews(query: NewsQuery, baseUrl?: string) {
    const [data, setData] = useState<NewsApiResponse | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState<boolean>(true);

    // stable key so effect triggers when query changes
    const key = useMemo(() => JSON.stringify(query), [query]);

    useEffect(() => {
        let cancelled = false;
        setLoading(true);
        setError(null);

        fetchNews(query, baseUrl)
            .then((d) => {
                if (cancelled) return;
                setData(d);
            })
            .catch((e: unknown) => {
                if (cancelled) return;
                setError(e instanceof Error ? e.message : String(e));
                setData(null);
            })
            .finally(() => {
                if (cancelled) return;
                setLoading(false);
            });

        return () => {
            cancelled = true;
        };
    }, [key, baseUrl]);

    return { data, error, loading };
}