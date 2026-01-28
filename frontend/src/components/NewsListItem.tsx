import type { NewsItem } from "../types/news";
import "./NewsListItem.css";

interface NewsListItemProps {
  Item: NewsItem;
}

const NewsListItem = ({ Item }: NewsListItemProps) => {
  const weekdays = ["Sunnuntai", "Maanantai", "Tiistai", "Keskiviikko", "Torstai", "Perjantai", "Lauantai", "Sunnuntai"];

  const returnWeekday = (DateString: string) => {
    const day = new Date(DateString).getDay();
    return weekdays[day];
  };

  return (
    <div className="newsListItem">
      <a className="newsListItem__title" href={Item.url} target="_blank" rel="noreferrer">
        {Item.title} &rsaquo;
      </a>

      {Item.summary && <p className="newsListItem__summary">{Item.summary}</p>}
      <div className="newsListItem__meta">
        <span className="newsListItem__date">{Item.publishedAt ? [returnWeekday(Item.publishedAt), new Date(Item.publishedAt).toLocaleString().replaceAll("/", ".")].join(" ") : ""}</span>
        <span key={Item.sources[0].id} className={["newsListItem__tag", `newsListItem__tag--${Item.sources[0].id}`].join(" ")}>{Item.sources[0].name}</span>
      </div>
    </div>
  );
};

export default NewsListItem;
export { NewsListItem };