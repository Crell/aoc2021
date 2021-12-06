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


class Point
{
    public function __construct(
        public readonly int $x,
        public readonly int $y,
    ) {}
}

class Line
{
    public function __construct(
        public readonly Point $start,
        public readonly Point $end,
    ) {}
}

class Grid
{
    public function __construct(
        public readonly array $grid = [],
    ) {}
}

/**
 * @return Line[]
 */
function parseInput(string $inputFile): iterable
{
    return pipe($inputFile,
        lines(...),
        itmap(makeLine(...)),
    );
}

function makeLine(string $line): Line
{
    return pipe($line,
        explode('->'),
        amap(trim(...)),
        amap(explode(',')),
        amap(makePoint(...)),
        static fn (array $points): Line => new Line($points[0], $points[1]),
    );
}

function makePoint(array $coords): Point
{
    return new Point((int)$coords[0], (int)$coords[1]);
}

function isOrthogonal(Line $line): bool
{
    return ($line->start->x === $line->end->x)
        || ($line->start->y === $line->end->y);
}

/**
 * @return Point[]
 */
function materializeLine(Line $line): iterable
{
    foreach (range($line->start->x, $line->end->x) as $x) {
        foreach (range($line->start->y, $line->end->y) as $y) {
            yield new Point($x, $y);
        }
    }
}

function markLine(Grid $old, iterable $points): Grid
{
    $grid = $old->grid;

    foreach ($points as $point) {
        $grid[$point->x][$point->y] = ($grid[$point->x][$point->y] ?? 0) + 1;
    }

    return new Grid($grid);
}

function countOverlaps(Grid $grid): int
{
    return pipe($grid->grid,
        flatten(...),
        afilter(static fn (int $cell): bool => $cell > 1),
        count(...),
    );
}



$in = parseInput($inputFile);

$overlaps = pipe($in,
    itfilter(isOrthogonal(...)),
    itmap(materializeLine(...)),
    reduce(new Grid, markLine(...)),
    countOverlaps(...),
);

print "Overlaps: $overlaps\n";
