import { useState } from "react";
import { useNews } from "./hooks/useNews";
import type { NewsQuery } from "./types/news";

export default function App() {
  const [query, setQuery] = useState<NewsQuery>({ bucket: "lapland" });
  const { data, error, loading } = useNews(query);

  return (
    <div style={{ maxWidth: 900, margin: "0 auto", padding: 16, fontFamily: "system-ui" }}>
      <h1>Breakfast News</h1>

      <div style={{ display: "flex", gap: 8, flexWrap: "wrap", marginBottom: 12 }}>
        <button onClick={() => setQuery({})}>All</button>
        <button onClick={() => setQuery({ bucket: "lapland" })}>Lapland</button>
        <button onClick={() => setQuery({ source: "yle_lappi" })}>Yle Lappi</button>
        <button onClick={() => setQuery({ source: "lapinkansa_lappi" })}>Lapin Kansa – Lappi</button>
      </div>

      {loading && <p>Loading…</p>}
      {error && <p style={{ color: "crimson" }}>{error}</p>}

      {data && (
        <>
          <p style={{ opacity: 0.7 }}>
            Updated: {data.fetchedAt} • Items: {data.itemsCount} • Sources: {data.sourcesCount}
          </p>

          {data.errors.length > 0 && (
            <details>
              <summary>Feed errors ({data.errors.length})</summary>
              <pre>{JSON.stringify(data.errors, null, 2)}</pre>
            </details>
          )}

          <ul style={{ listStyle: "none", padding: 0 }}>
            {data.items.slice(0, 30).map((item) => (
              <li key={item.id} style={{ padding: "10px 0", borderBottom: "1px solid #ddd" }}>
                <a href={item.url} target="_blank" rel="noreferrer" style={{ fontWeight: 600 }}>
                  {item.title}
                </a>

                <div style={{ fontSize: 12, opacity: 0.75, marginTop: 4 }}>
                  {item.publishedAt ? new Date(item.publishedAt).toLocaleString() : "—"} •{" "}
                  {item.sources.map((s) => s.name).join(", ")}
                </div>
              </li>
            ))}
          </ul>
        </>
      )}
    </div>
  );
}