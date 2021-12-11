<?php

declare(strict_types=1);

use function Crell\fp\amap;
use function Crell\fp\explode;
use function Crell\fp\pipe;

require_once __DIR__ . '/../vendor/autoload.php';

$inputFile = __DIR__ . '/input.txt';

function average(array $list): array
{
    $avg = array_sum($list) / count($list);
    return is_float($avg)
        ? [floor($avg), ceil($avg)]
        : [$avg];
}

function cost(int $x, int $target): int
{
    $steps = abs($x - $target);
    return ($steps * (1 + $steps)) / 2;
}

$data = pipe($inputFile,
    file_get_contents(...),
    trim(...),
    explode(','),
    amap(intval(...)),
);

$costFinder = static fn (int $avg): int => pipe($data,
    amap(static fn (int $i): int => cost($i, $avg)),
    array_sum(...),
);

$totalDistance = pipe($data,
    average(...),
    amap($costFinder(...)),
    min(...),
);

print "Cost: $totalDistance\n";
