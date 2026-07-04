<?php

declare(strict_types=1);

use Zarbin\IranLocations\Data\ExcelLocationDataConverter;
use Zarbin\IranLocations\Support\PersianCoreLocationNormalizer;
use Zarbinco\PersianCore\Formatters\MobileFormatter;
use Zarbinco\PersianCore\Formatters\MoneyFormatter;
use Zarbinco\PersianCore\Formatters\NumberFormatter;
use Zarbinco\PersianCore\Normalizers\MobileNormalizer;
use Zarbinco\PersianCore\Normalizers\MoneyNormalizer;
use Zarbinco\PersianCore\Normalizers\PersianNormalizerPipeline;
use Zarbinco\PersianCore\Normalizers\PersianNumberNormalizer;
use Zarbinco\PersianCore\Normalizers\PersianSearchNormalizer;
use Zarbinco\PersianCore\Normalizers\PersianTextNormalizer;
use Zarbinco\PersianCore\PersianManager;
use Zarbinco\PersianCore\Services\IranianBankDetector;

require __DIR__.'/../vendor/autoload.php';

$sourcePath = $argv[1] ?? dirname(__DIR__).DIRECTORY_SEPARATOR.'_source'.DIRECTORY_SEPARATOR.'iran-locations'.DIRECTORY_SEPARATOR.'excel';
$outputPath = $argv[2] ?? dirname(__DIR__).DIRECTORY_SEPARATOR.'data';

$numberNormalizer = new PersianNumberNormalizer;
$textNormalizer = new PersianTextNormalizer;
$searchNormalizer = new PersianSearchNormalizer($numberNormalizer);
$moneyNormalizer = new MoneyNormalizer($numberNormalizer);
$mobileNormalizer = new MobileNormalizer($numberNormalizer);
$pipeline = new PersianNormalizerPipeline($textNormalizer, $numberNormalizer, [], $searchNormalizer);

$normalizer = new PersianCoreLocationNormalizer(new PersianManager(
    $textNormalizer,
    $numberNormalizer,
    new NumberFormatter($numberNormalizer),
    $moneyNormalizer,
    new MoneyFormatter($moneyNormalizer, $numberNormalizer),
    $mobileNormalizer,
    new MobileFormatter($mobileNormalizer),
    $pipeline,
    $searchNormalizer,
    new IranianBankDetector($numberNormalizer),
));

$summary = (new ExcelLocationDataConverter($normalizer))->convertDirectory($sourcePath, $outputPath);

echo 'Converted Iran Locations Excel data to JSON.'.PHP_EOL;
echo 'Output: '.$outputPath.PHP_EOL;
echo 'Counts: '.json_encode($summary['counts'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;

if ($summary['skipped'] !== []) {
    echo 'Skipped rows: '.count($summary['skipped']).PHP_EOL;
}

if ($summary['duplicates'] !== []) {
    echo 'Duplicate source observations: '.count($summary['duplicates']).PHP_EOL;
}

if ($summary['missing_references'] !== []) {
    echo 'Missing references: '.count($summary['missing_references']).PHP_EOL;
}
