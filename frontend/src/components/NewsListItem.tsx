import type { NewsItem } from "../types/news";
import "./NewsListItem.css";

interface NewsListItemProps {
  Item: NewsItem;
}

const NewsListItem = ({ Item }: NewsListItemProps) => {
  return (
    <div className="newsListItem">
      <a className="newsListItem__title" href={Item.url} target="_blank" rel="noreferrer">
        {Item.title} &rsaquo;
      </a>

      {Item.summary && <p>{Item.summary}</p>}
      <div className="newsListItem__meta">
        <span className="newsListItem__date">{Item.publishedAt ? new Date(Item.publishedAt).toLocaleString().replaceAll("/", ".") : "—"}</span>
        {Item.sources.map((s) => (<span key={s.id} className="newsListItem__tag">{s.name}</span>))}
      </div>
    </div>
  );
};

export default NewsListItem;
export { NewsListItem };