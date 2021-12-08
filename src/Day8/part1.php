<?php

declare(strict_types=1);

use function Crell\fp\amap;
use function Crell\fp\itmap;
use function Crell\fp\afilter;
use function Crell\fp\flatten;
use function Crell\fp\explode;
use function Crell\fp\pipe;

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

class Line
{
    public function __construct(
        public readonly array $in,
        public readonly array $out,
    ) {}

    public static function fromInput(array $args): static
    {
        return new static($args[0], $args[1]);
    }
}

function parseLine(string $line): Line
{
    return pipe($line,
        explode(' | '),
        amap(explode(' ')),
        amap(afilter()),
        Line::fromInput(...),
    );
}

function countPossibilities(int $in): array
{
    // 0 and 1 are not possible, so ignore those.
    return match ($in) {
        2 => [1],
        3 => [7],
        4 => [4],
        5 => [2, 3, 5],
        6 => [0, 6, 9],
        7 => [8],
    };
}

$number = pipe($inputFile,
    lines(...),
    amap(parseLine(...)),
    amap(static fn (Line $l): array => $l->out),
    flatten(...),
    amap(strlen(...)),
    amap(countPossibilities(...)),
    afilter(static fn (array $possibilities): bool => count($possibilities) === 1),
    count(...),
);

print "Number of easy digits: $number\n";
