<?php
declare(strict_types=1);

// PHP < 8 compatibility (cron/CLI on some hosts)
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

function ensureDir(string $dir): void
{
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Failed to create directory: {$dir}");
        }
    }
}

function withLock(string $lockFile, callable $fn): void
{
    ensureDir(dirname($lockFile));
    $fp = fopen($lockFile, 'c');
    if ($fp === false) {
        throw new RuntimeException("Cannot open lock file: {$lockFile}");
    }

    try {
        if (!flock($fp, LOCK_EX)) {
            throw new RuntimeException("Cannot obtain lock: {$lockFile}");
        }
        $fn();
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

function httpGet(string $url, int $timeoutSeconds = 60): string
{
    // Prefer cURL (most common on shared hosting)
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
            CURLOPT_TIMEOUT        => $timeoutSeconds,
            CURLOPT_USERAGENT      => 'MyBreakfastNewsBot/0.1 (+local POC)',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/rss+xml, application/xml;q=0.9, */*;q=0.8'
            ],
        ]);

        $body = curl_exec($ch);
        if ($body === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("cURL error: {$err}");
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("HTTP status {$status} fetching {$url}");
        }

        return (string)$body;
    }

    // Fallback
    $ctx = stream_context_create([
        'http' => [
            'timeout' => $timeoutSeconds,
            'header'  => "User-Agent: MyBreakfastNewsBot/0.1 (+local POC)\r\nAccept: application/rss+xml, application/xml;q=0.9, */*;q=0.8\r\n",
        ],
    ]);

    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        throw new RuntimeException("file_get_contents failed fetching {$url}");
    }
    return (string)$body;
}

/**
 * Canonicalize URLs for dedupe:
 * - remove common tracking params (utm_*, gclid, fbclid, etc.)
 * - keep the rest intact
 */
function canonicalizeUrl(string $url): string
{
    $url = trim($url);
    if ($url === '') return $url;

    $parts = parse_url($url);
    if ($parts === false) return $url;

    $scheme = $parts['scheme'] ?? null;
    $host   = $parts['host'] ?? null;
    $path   = $parts['path'] ?? '';
    $query  = $parts['query'] ?? '';

    if (!$scheme || !$host) return $url;

    $filteredQuery = '';
    if ($query !== '') {
        parse_str($query, $params);

        $deny = [
            'gclid', 'fbclid', 'yclid', 'mc_cid', 'mc_eid',
        ];

        foreach (array_keys($params) as $k) {
            $lk = strtolower((string)$k);
            if (str_starts_with($lk, 'utm_')) {
                unset($params[$k]);
                continue;
            }
            if (in_array($lk, $deny, true)) {
                unset($params[$k]);
                continue;
            }
        }

        if (!empty($params)) {
            $filteredQuery = http_build_query($params);
        }
    }

    $canon = "{$scheme}://{$host}{$path}";
    if ($filteredQuery !== '') {
        $canon .= "?{$filteredQuery}";
    }

    // ignore fragments
    return $canon;
}

function writeJsonAtomically(string $path, array $data): void
{
    ensureDir(dirname($path));

    $tmp  = $path . '.tmp';
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) {
        throw new RuntimeException("json_encode failed.");
    }

    if (file_put_contents($tmp, $json) === false) {
        throw new RuntimeException("Failed writing temp file: {$tmp}");
    }

    if (!rename($tmp, $path)) {
        @unlink($tmp);
        throw new RuntimeException("Failed replacing cache file: {$path}");
    }
}

function readJsonFile(string $path): ?array
{
    if (!file_exists($path)) return null;
    $raw = file_get_contents($path);
    if ($raw === false) return null;

    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function isFileFresh(string $path, int $ttlSeconds): bool
{
    if (!file_exists($path)) return false;
    $age = time() - (int)filemtime($path);
    return $age >= 0 && $age <= $ttlSeconds;
}

function cleanSummaryText(string $htmlOrText, int $maxLen): ?string
{
    $text = trim($htmlOrText);
    if ($text === '') return null;

    // Strip HTML tags (RSS descriptions often contain HTML)
    $text = strip_tags($text);

    // Decode entities (&amp; etc.)
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Normalize whitespace
    $text = preg_replace('/\s+/u', ' ', trim($text));
    if ($text === '' || $text === null) return null;

    // Truncate safely
    if ($maxLen > 0 && mb_strlen($text, 'UTF-8') > $maxLen) {
        $text = mb_substr($text, 0, $maxLen, 'UTF-8');
        $text = rtrim($text);
        $text .= '…';
    }

    return $text;
}

function extractRssSummary(SimpleXMLElement $item, int $maxLen): ?string
{
    // Prefer <description>
    $desc = trim((string)($item->description ?? ''));
    if ($desc !== '') {
        return cleanSummaryText($desc, $maxLen);
    }

    // Fallback: content:encoded (some feeds)
    $contentNs = $item->children('http://purl.org/rss/1.0/modules/content/');
    if ($contentNs && isset($contentNs->encoded)) {
        $encoded = trim((string)$contentNs->encoded);
        if ($encoded !== '') {
            return cleanSummaryText($encoded, $maxLen);
        }
    }

    return null;
}

/**
 * Parse RSS 2.0 -> common item model.
 * Keeps it headline-oriented: title + url + publishedAt + categories.
 * Added summary as well.
 */
function parseRssToItems(string $xmlString, array $source): array
{
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($xml === false) {
        $errs = array_map(fn($e) => trim($e->message), libxml_get_errors());
        libxml_clear_errors();
        throw new RuntimeException("XML parse error: " . implode(" | ", $errs));
    }

    $channel = $xml->channel ?? null;
    if (!$channel) {
        throw new RuntimeException("Unexpected RSS format (missing channel).");
    }

    $includeSummary = (bool)($source['includeSummary'] ?? false);
    $summaryMaxLen  = (int)($source['summaryMaxLen'] ?? 240);

    $items = [];
    foreach ($channel->item ?? [] as $item) {
        $title = trim((string)($item->title ?? ''));
        $link  = trim((string)($item->link ?? ''));

        if ($title === '' || $link === '') continue;

        $pub = trim((string)($item->pubDate ?? ''));
        $publishedAt = null;
        if ($pub !== '') {
            $ts = strtotime($pub);
            if ($ts !== false) $publishedAt = date(DATE_ATOM, $ts);
        }

        $categories = [];
        foreach ($item->category ?? [] as $cat) {
            $c = trim((string)$cat);
            if ($c !== '') $categories[] = $c;
        }
        $categories = array_values(array_unique($categories));

        $canonUrl = canonicalizeUrl($link);

        $summary = null;
        if ($includeSummary) {
            $summary = extractRssSummary($item, $summaryMaxLen);
        }

        $items[] = [
            'id'           => sha1($canonUrl),
            'title'        => $title,
            'url'          => $link,
            'canonicalUrl' => $canonUrl,
            'publishedAt'  => $publishedAt,
            'categories'   => $categories,
            'summary'      => $summary, // <-- new

            'sourceId'     => (string)$source['id'],
            'sourceName'   => (string)$source['name'],
            'bucket'       => (string)($source['bucket'] ?? 'default'),
            'lang'         => (string)($source['lang'] ?? ''),
        ];
    }

    return $items;
}

/**
 * Merge items from multiple sources by canonicalUrl (id = sha1(canonicalUrl)).
 * Combines sources into sources[]; keeps the "best" title (first seen) and earliest/non-null publishedAt.
 */
function mergeItemsByCanonicalUrl(array $allItems): array
{
    $map = [];

    foreach ($allItems as $it) {
        $key = $it['id'] ?? null;
        if (!$key) continue;

        if (!isset($map[$key])) {
            $map[$key] = [
                'id'          => $it['id'],
                'title'       => $it['title'],
                'url'         => $it['url'],
                'canonicalUrl'=> $it['canonicalUrl'],
                'publishedAt' => $it['publishedAt'],
                'categories'  => $it['categories'] ?? [],
                'bucket'      => $it['bucket'] ?? 'default',
                'lang'        => $it['lang'] ?? '',
                'summary' => $it['summary'] ?? null,
                'sources'     => [
                    [
                        'id'   => $it['sourceId'],
                        'name' => $it['sourceName'],
                    ]
                ],
            ];
            continue;
        }

        // Merge sources
        $existingSources = $map[$key]['sources'];
        $exists = false;
        foreach ($existingSources as $s) {
            if (($s['id'] ?? '') === ($it['sourceId'] ?? '')) {
                $exists = true; break;
            }
        }
        if (!$exists) {
            $map[$key]['sources'][] = [
                'id'   => $it['sourceId'],
                'name' => $it['sourceName'],
            ];
        }

        // Merge categories
        $cats = array_merge($map[$key]['categories'] ?? [], $it['categories'] ?? []);
        $map[$key]['categories'] = array_values(array_unique(array_filter($cats)));

        // summary: prefer a non-null, longer summary
        $cur = $map[$key]['summary'] ?? null;
        $new = $it['summary'] ?? null;
        if (($cur === null || $cur === '') && ($new !== null && $new !== '')) {
            $map[$key]['summary'] = $new;
        } elseif ($cur !== null && $new !== null) {
            if (mb_strlen($new, 'UTF-8') > mb_strlen($cur, 'UTF-8')) {
                $map[$key]['summary'] = $new;
            }
        }

        // publishedAt: prefer non-null; if both non-null, keep the newest? (breakfast feed wants newest)
        $a = $map[$key]['publishedAt'];
        $b = $it['publishedAt'];
        if ($a === null && $b !== null) {
            $map[$key]['publishedAt'] = $b;
        } elseif ($a !== null && $b !== null) {
            if (strtotime($b) > strtotime($a)) {
                $map[$key]['publishedAt'] = $b;
            }
        }

        // Keep existing title/url (first seen) to keep stable.
    }

    $merged = array_values($map);

    // Sort newest first; nulls last
    usort($merged, function ($x, $y) {
        $ax = $x['publishedAt'] ?? null;
        $ay = $y['publishedAt'] ?? null;
        if ($ax === $ay) return 0;
        if ($ax === null) return 1;
        if ($ay === null) return -1;
        return strtotime($ay) <=> strtotime($ax);
    });

    return $merged;
}

?>