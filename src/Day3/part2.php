<?php

declare(strict_types=1);

use function Crell\fp\afilter;
use function Crell\fp\reduce;
use function Crell\fp\itmap;
use function Crell\fp\amap;
use function Crell\fp\pipe;
use function Crell\fp\trace;

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

class BitCount
{
    public function __construct(
        public readonly int $entryCount = 0,
        public readonly array $counts = [],
    ) {}
}

function masks(int $size): array
{
    return array_reverse(amap(static fn (int $i) => 2 ** $i)(range(0, $size - 1)));
}

function countBits(BitCount $bitCount, int $next, array $masks): BitCount
{
    $c = static fn (int $count, int $mask) => $next & $mask ? $count + 1 : $count;

    $state = array_map($c, $bitCount->counts, $masks);
    return new BitCount($bitCount->entryCount + 1, $state);
}

function computeGamma(BitCount $bitCount, array $masks): int
{
    $c = static fn (int $count, int $mask) => ($count * 2 >= $bitCount->entryCount) * $mask;

    return array_sum(array_map($c, $bitCount->counts, $masks));
}

function computeEpsilon(BitCount $bitCount, array $masks): int
{
    $c = static fn (int $count, int $mask) => ($count * 2 < $bitCount->entryCount) * $mask;

    return array_sum(array_map($c, $bitCount->counts, $masks));
}

$masks = masks(12);

$diags = pipe($inputFile,
    lines(...),
    amap(bindec(...)),
);

function bitCounter(array $diags, $masks): BitCount
{
    $bitCounter = static fn(BitCount $counter, int $next): BitCount => countBits($counter, $next, $masks);
    $initalCounts = array_fill(0, count($masks), 0);

    return pipe($diags,
        reduce(new BitCount(counts: $initalCounts), $bitCounter),
    );
}

$counts = bitCounter($diags, $masks);

$gamma = computeGamma($counts, $masks);
$epsilon = computeEpsilon($counts, $masks);

print "Gamma: $gamma, Epsilon: $epsilon\nProduct: " . $epsilon * $gamma . PHP_EOL;

function oxCriteria(BitCount $bitCount, int $position): int
{
    $oneIsMoreCommon = $bitCount->counts[$position] * 2 >= $bitCount->entryCount;
    return $oneIsMoreCommon ? 1 : 0;
}

function coCriteria(BitCount $bitCount, int $position): int
{
    $oneIsLessCommon = $bitCount->counts[$position] * 2 < $bitCount->entryCount;
    return $oneIsLessCommon ? 1 : 0;
}

function atPosition($number, $position): int
{
    return $number >> (11 - $position) & 1;
}

function findGas(array $masks, callable $criteria, array $diags, int $position = 0): int
{
    if (count($diags) === 1) {
        return current($diags);
    }

    $bitCount = bitCounter($diags, $masks);

    $check = $criteria($bitCount, $position);

    $filter = static fn ($number) => $check === atPosition($number, $position);

    $filtered = afilter($filter)($diags);
    return findGas($masks, $criteria, $filtered, $position + 1);
}

$o2level = findGas($masks, oxCriteria(...), $diags);
$co2level = findGas($masks, coCriteria(...), $diags);

print "O2: $o2level, CO2: $co2level\nProduct: " . $o2level * $co2level . PHP_EOL;
