<?php

declare(strict_types=1);

use function Crell\fp\amap;
use function Crell\fp\afilter;
use function Crell\fp\reduce;
use function Crell\fp\compose;
use function Crell\fp\explode;
use function Crell\fp\pipe;
use function Crell\fp\amapWithKeys;

require_once __DIR__ . '/../vendor/autoload.php';

$inputFile = __DIR__ . '/input.txt';

function lines(string $file): iterable
{
    $fp = fopen($file, 'rb');

    while ($line = fgets($fp)) {
        yield trim($line);
    }

    fclose($fp);
}

class Position
{
    public function __construct(
        public readonly int $value,
        public readonly array $neighborValues,
    ) {}
}

function buildNeighborMap(array $heights): iterable
{
    foreach ($heights as $r => $row) {
        foreach ($row as $c => $val) {
            $neighbors = array_filter([
                $heights[$r-1][$c] ?? null,
                $heights[$r][$c-1] ?? null,
                $heights[$r+1][$c] ?? null,
                $heights[$r][$c+1] ?? null,
            ], fn($v): bool => !is_null($v));
            yield new Position($val, $neighbors);
        }
    }
}

function isLowPoint(Position $p): bool
{
    return $p->value < min($p->neighborValues);
}

function computeRisk(Position $p): int
{
    return $p->value +1;
}

$riskSum = pipe($inputFile,
    lines(...),
    amap(compose(str_split(...), amap(intval(...)))),
    buildNeighborMap(...),
    afilter(isLowPoint(...)),
    amap(computeRisk(...)),
    array_sum(...),
);

print "The risk sum is: $riskSum\n";
