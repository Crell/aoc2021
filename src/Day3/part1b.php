<?php

declare(strict_types=1);

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

$initalCounts = array_fill(0, 12, 0);

$bitCounter = static fn(BitCount $counter, int $next): BitCount => countBits($counter, $next, $masks);

$counts = pipe($inputFile,
    lines(...),
    itmap(bindec(...)),
    reduce(new BitCount(counts: $initalCounts), $bitCounter),
);

$gamma = computeGamma($counts, $masks);
$epsilon = computeEpsilon($counts, $masks);

print "Gamma: $gamma, Epsilon: $epsilon\nProduct: " . $epsilon * $gamma . PHP_EOL;