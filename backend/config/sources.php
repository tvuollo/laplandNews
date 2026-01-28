<?php
declare(strict_types=1);

return [
    [
        'id'     => 'yle_lappi',
        'name'   => 'Yle Lappi',
        'rssUrl' => 'https://yle.fi/rss/t/18-139752/fi',
        'bucket' => 'lapland',
        'lang'   => 'fi',
        'includeSummary' => false,
        'summaryMaxLen'  => 240,
    ],
    [
        'id'     => 'yle_rovaniemi',
        'name'   => 'Yle Rovaniemi',
        'rssUrl' => 'https://yle.fi/rss/t/18-186749/fi',
        'bucket' => 'rovaniemi',
        'lang'   => 'fi',
        'includeSummary' => false,
        'summaryMaxLen'  => 240,
    ],
    [
        'id'     => 'lapinkansa_lappi',
        'name'   => 'Lapin Kansa – Lappi',
        'rssUrl' => 'https://www.lapinkansa.fi/feedit/rss/managed-listing/lappi/',
        'bucket' => 'lapland',
        'lang'   => 'fi',
        'includeSummary' => true,
        'summaryMaxLen'  => 240,
    ],
    [
        'id'     => 'rovaniemenkaupunki',
        'name'   => 'Rovaniemen kaupunki',
        'rssUrl' => 'https://www.rovaniemi.fi/Ajankohtaista/Uutiset.rss',
        'bucket' => 'rovaniemi',
        'lang'   => 'fi',
        'includeSummary' => true,
        'summaryMaxLen'  => 240,
    ],
    [
        'id'     => 'lapinpoliisilaitos',
        'name'   => 'Lapin Poliisilaitos',
        'rssUrl' => 'https://poliisi.fi/lapin-poliisilaitos/-/asset_publisher/ZtAEeHB39Lxr/rss',
        'bucket' => 'lapland',
        'lang'   => 'fi',
        'includeSummary' => true,
        'summaryMaxLen'  => 240,
    ],
];
?>