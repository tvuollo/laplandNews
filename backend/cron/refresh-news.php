<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/rss_lib.php';

const TTL_SECONDS = 4 * 60 * 60; // 4 hours

$baseStorage = __DIR__ . '/../storage/cache';
$lockFile    = $baseStorage . '/refresh.lock';
$feedsDir    = $baseStorage . '/feeds';
$allCache    = $baseStorage . '/all.json';

$sources = require __DIR__ . '/../config/sources.php';

withLock($lockFile, function () use ($sources, $feedsDir, $allCache) {
    ensureDir($feedsDir);

    $allItems = [];
    $errors = [];

    foreach ($sources as $source) {
        $id = (string)$source['id'];
        if ($id === '') continue;

        $feedCache = $feedsDir . '/' . $id . '.json';

        // TTL per-feed: if fresh, reuse cached items
        if (isFileFresh($feedCache, TTL_SECONDS)) {
            $cached = readJsonFile($feedCache);
            if (is_array($cached) && isset($cached['items']) && is_array($cached['items'])) {
                $allItems = array_merge($allItems, $cached['items']);
                continue;
            }
            // If cache is corrupted, fall through and refresh.
        }

        try {
            $xml = httpGet((string)$source['rssUrl']);
            $items = parseRssToItems($xml, $source);

            $feedPayload = [
                'source' => [
                    'id'   => $source['id'],
                    'name' => $source['name'],
                    'rssUrl' => $source['rssUrl'],
                    'bucket' => $source['bucket'] ?? 'default',
                    'lang'   => $source['lang'] ?? '',
                ],
                'fetchedAt' => date(DATE_ATOM),
                'count'     => count($items),
                'items'     => $items,
            ];

            writeJsonAtomically($feedCache, $feedPayload);

            $allItems = array_merge($allItems, $items);
        } catch (Throwable $e) {
            $errors[] = [
                'sourceId' => $source['id'] ?? '',
                'message'  => $e->getMessage(),
            ];

            // If we have an older cache, use it as fallback
            $cached = readJsonFile($feedCache);
            if (is_array($cached) && isset($cached['items']) && is_array($cached['items'])) {
                $allItems = array_merge($allItems, $cached['items']);
            }
        }
    }

    $merged = mergeItemsByCanonicalUrl($allItems);

    // Hide items older than 30 days (based on publishedAt)
    $maxAgeDays = 30;
    $cutoffTs = time() - ($maxAgeDays * 24 * 60 * 60); // 30 days in seconds

    $merged = array_values(array_filter($merged, function ($it) use ($cutoffTs) {
        $p = $it['publishedAt'] ?? null;

        // If missing/invalid date, keep it (or return false if you prefer to hide undated items too)
        if ($p === null || $p === '') return true;

        $ts = strtotime($p);
        if ($ts === false) return true;

        return $ts >= $cutoffTs;
    }));

    $payload = [
        'fetchedAt'     => date(DATE_ATOM),
        'ttlSeconds'    => TTL_SECONDS,
        'sourcesCount'  => count($sources),
        'itemsCount'    => count($merged),
        'errors'        => $errors,
        'items'         => $merged,
    ];

    writeJsonAtomically($allCache, $payload);
});

echo "OK\n";

?>