<?php

declare(strict_types=1);

use Zarbin\IranLocations\Data\SqlLocationDataConverter;

require __DIR__.'/../vendor/autoload.php';

$sourcePath = $argv[1] ?? getcwd();
$outputPath = $argv[2] ?? dirname(__DIR__).DIRECTORY_SEPARATOR.'data';

$summary = (new SqlLocationDataConverter)->convertDirectory($sourcePath, $outputPath);

echo 'Converted Iran Locations SQL data to JSON.'.PHP_EOL;
echo 'Output: '.$outputPath.PHP_EOL;
echo 'Counts: '.json_encode($summary['counts'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;

if ($summary['skipped'] !== []) {
    echo 'Skipped rows: '.count($summary['skipped']).PHP_EOL;
}

if ($summary['missing_references'] !== []) {
    echo 'Missing references: '.count($summary['missing_references']).PHP_EOL;
}
