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
    static $cache;
    return $cache[$size] ??= pipe(range(0, $size - 1),
        amap(static fn (int $i) => 2 ** $i),
        array_reverse(...),
    );
}

function countBits(BitCount $bitCount, int $next): BitCount
{
    $c = static fn (int $count, int $mask) => $next & $mask ? $count + 1 : $count;

    $state = array_map($c, $bitCount->counts, masks(12));
    return new BitCount($bitCount->entryCount + 1, $state);
}

function computeGamma(BitCount $bitCount): int
{
    $c = static fn (int $count, int $mask) => ($count * 2 >= $bitCount->entryCount) * $mask;

    return array_sum(array_map($c, $bitCount->counts, masks(12)));
}

function computeEpsilon(BitCount $bitCount): int
{
    $c = static fn (int $count, int $mask) => ($count * 2 < $bitCount->entryCount) * $mask;

    return array_sum(array_map($c, $bitCount->counts, masks(12)));
}

$counts = pipe($inputFile,
    lines(...),
    itmap(bindec(...)),
    reduce(new BitCount(counts: array_fill(0, 12, 0)), countBits(...)),
);

$gamma = computeGamma($counts);
$epsilon = computeEpsilon($counts);

print "Gamma: $gamma, Epsilon: $epsilon\nProduct: " . $epsilon * $gamma . PHP_EOL;