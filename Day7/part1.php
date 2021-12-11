<?php

declare(strict_types=1);

use function Crell\fp\amap;
use function Crell\fp\explode;
use function Crell\fp\pipe;

require_once __DIR__ . '/../vendor/autoload.php';

$inputFile = __DIR__ . '/input.txt';

function numSort(array $a): array
{
    sort($a);
    return $a;
}

function medians(array $sorted): array
{
    $idx = (count($sorted) + 1) / 2;
    return is_float($idx)
        ? [$sorted[floor($idx)], $sorted[ceil($idx)]]
        : [$sorted[$idx]];
}

function cost(int $x, int $target): int
{
    return abs($x - $target);
}

$data = pipe($inputFile,
    file_get_contents(...),
    trim(...),
    explode(','),
    amap(intval(...)),
);

$costFinder = static fn (int $median): int => pipe($data,
    amap(static fn (int $i): int => cost($i, $median)),
    array_sum(...),
);

$totalDistance = pipe($data,
    numSort(...),
    medians(...),
    amap($costFinder(...)),
    min(...),
);

print "Distance: $totalDistance\n";
