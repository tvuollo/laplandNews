import { useState } from "react";
import { useNews } from "./hooks/useNews";
import type { NewsQuery } from "./types/news";
import NewsListItem from "./components/NewsListItem";
import "./App.css";

export default function App() {
  const [query, setQuery] = useState<NewsQuery>({});
  const { data, error, loading } = useNews(query);

  return (
    <div className="app">
      <h1>Uutisia Lapista</h1>

      <div style={{ display: "none" }}>
        <button onClick={() => setQuery({})}>All</button>
        <button onClick={() => setQuery({ bucket: "lapland" })}>Lappi</button>
        <button onClick={() => setQuery({ bucket: "rovaniemi" })}>Rovaniemi</button>
        <button onClick={() => setQuery({ source: "yle_lappi" })}>Yle Lappi</button>
        <button onClick={() => setQuery({ source: "yle_rovaniemi" })}>Yle Rovaniemi</button>
        <button onClick={() => setQuery({ source: "lapinkansa_lappi" })}>Lapin Kansa – Lappi</button>
        <button onClick={() => setQuery({ source: "rovaniemenkaupunki" })}>Rovaniemen kaupunki</button>
        <button onClick={() => setQuery({ source: "lapinpoliisilaitos" })}>Lapin Poliisilaitos</button>
      </div>

      {loading && <p>Haetaan uutisia...</p>}
      {error && <p>{error}</p>}

      {!loading && data && (
        <>
          <p>
            <small>Päivitetty: {new Date(data.fetchedAt).toLocaleString().replaceAll("/", ".")}</small>
          </p>

          {data.errors.length > 0 && (
            <details>
              <summary>Feed errors ({data.errors.length})</summary>
              <pre>{JSON.stringify(data.errors, null, 2)}</pre>
            </details>
          )}

          <ul className="newsList">
            {data.items.map((item) => (
              <li className="newsList__li" key={item.id}>
                <NewsListItem key={item.id} Item={item} />
              </li>
            ))}
          </ul>
        </>
      )}
    </div>
  );
}