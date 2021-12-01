<?php

declare(strict_types=1);

use function Crell\fp\afilter;
use function Crell\fp\explode;
use function Crell\fp\pipe;
use function Crell\fp\trace;

require_once __DIR__ . '/../../vendor/autoload.php';

function pairUp(array $vals): array
{
    $ret = [];
    foreach ($vals as $i => $val) {
        $ret[] = [$val, $vals[$i - 1] ?? PHP_INT_MAX];
    }
    return $ret;
}

function makeSums(array $vals): array
{
    $sum = [];
    $count = count($vals);
    for ($i = 2; $i < $count; ++$i) {
        $sum[] = $vals[$i] + $vals[$i - 1] + $vals[$i - 2];
    }
    return $sum;
}

$inputFile = __DIR__ . '/input.txt';

$result = pipe($inputFile,
    file_get_contents(...),
    trim(...),
    explode(PHP_EOL),
    makeSums(...),
    pairUp(...),
    afilter(static fn($v): bool => $v[0] > $v[1]),
    count(...),
);

print $result . PHP_EOL;
