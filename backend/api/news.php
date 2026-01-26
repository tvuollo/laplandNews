<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/rss_lib.php';

const ALL_CACHE = __DIR__ . '/../storage/cache/all.json';
const TTL_SECONDS = 4 * 60 * 60;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=120'); // browser cache 2 min (optional)

if (!file_exists(ALL_CACHE)) {
    http_response_code(503);
    echo json_encode(['error' => 'Cache not generated yet. Run cron once.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$age = time() - (int)filemtime(ALL_CACHE);
header('X-Cache: ' . ($age <= TTL_SECONDS ? 'HIT' : 'STALE'));

$data = readJsonFile(ALL_CACHE);
if (!is_array($data)) {
    http_response_code(500);
    echo json_encode(['error' => 'Cache read/parse failed.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$bucket = isset($_GET['bucket']) ? trim((string)$_GET['bucket']) : '';
$source = isset($_GET['source']) ? trim((string)$_GET['source']) : '';

if (($bucket !== '' || $source !== '') && isset($data['items']) && is_array($data['items'])) {
    $items = $data['items'];

    if ($bucket !== '') {
        $items = array_values(array_filter($items, fn($it) => (($it['bucket'] ?? '') === $bucket)));
    }

    if ($source !== '') {
        $items = array_values(array_filter($items, function ($it) use ($source) {
            $sources = $it['sources'] ?? [];
            if (!is_array($sources)) return false;
            foreach ($sources as $s) {
                if (($s['id'] ?? '') === $source) return true;
            }
            return false;
        }));
    }

    $data['items'] = $items;
    $data['itemsCount'] = count($items);
}

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

?>