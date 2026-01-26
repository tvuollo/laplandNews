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
        'id'     => 'lapinkansa_lappi',
        'name'   => 'Lapin Kansa – Lappi',
        'rssUrl' => 'https://www.lapinkansa.fi/feedit/rss/managed-listing/lappi/',
        'bucket' => 'lapland',
        'lang'   => 'fi',
        'includeSummary' => true,
        'summaryMaxLen'  => 240,
    ],
    // Add more feeds later like this:
    // [
    //   'id' => 'rovaniemi_city',
    //   'name' => 'Rovaniemen kaupunki',
    //   'rssUrl' => 'https://www.rovaniemi.fi/Ajankohtaista/Uutiset.rss',
    //   'bucket' => 'rovaniemi',
    //   'lang' => 'fi',
    // ],
];
?>