<?php

declare(strict_types=1);

use function Crell\fp\afilter;
use function Crell\fp\amap;
use function Crell\fp\explode;
use function Crell\fp\flatten;
use function Crell\fp\itfilter;
use function Crell\fp\itmap;
use function Crell\fp\pipe;
use function Crell\fp\reduce;

require_once __DIR__ . '/../../vendor/autoload.php';

$inputFile = __DIR__ . '/input.txt';

function lines(string $file): iterable
{
    $fp = fopen($file, 'rb');

    while ($line = fgets($fp)) {
        yield trim($line);
    }

    fclose($fp);
}

// Don't do this.
function oneFishEvolver(array $nextgen, int $fish): array
{
    if ($fish === 0) {
        $nextgen[] = 6;
        $nextgen[] = 8;
    } else {
        $nextgen[] = $fish - 1;
    }
    return $nextgen;
};

function fishMapper(array $fishCounter): array
{
    return [
        0 => $fishCounter[1],
        1 => $fishCounter[2],
        2 => $fishCounter[3],
        3 => $fishCounter[4],
        4 => $fishCounter[5],
        5 => $fishCounter[6],
        6 => $fishCounter[7] + $fishCounter[0],
        7 => $fishCounter[8],
        8 => $fishCounter[0],
    ];
}

function fishCounter(array $fish): callable
{
    return static fn (int $number): int => pipe($fish,
        afilter(static fn (int $f): bool => $f === $number),
        count(...),
    );
}

function fishSummarizer(array $fish): array
{
    return pipe(range(0, 8),
        amap(fishCounter($fish)),
    );
}

$fishCounter = pipe($inputFile,
    file_get_contents(...),
    trim(...),
    explode(','),
    fishSummarizer(...),
);

$total = pipe(range(1, 80),
    reduce($fishCounter, static fn (array $counts, int $gen): array => fishMapper($counts)),
    array_sum(...),
);

print "Total fish: $total\n";
