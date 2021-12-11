<?php

declare(strict_types=1);

use function Crell\fp\amap;
use function Crell\fp\append;
use function Crell\fp\collect;
use function Crell\fp\compose;
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

class Position
{
    public function __construct(
        public readonly int $value,
        public readonly int $x,
        public readonly int $y,
        public readonly array $neighborValues,
    ) {}
}

class Point
{
    public function __construct(
        public readonly int $x,
        public readonly int $y,
    ) {}

    public function left(): static
    {
        return new static($this->x - 1, $this->y);
    }

    public function right(): static
    {
        return new static($this->x + 1, $this->y);
    }

    public function above(): static
    {
        return new static($this->x, $this->y - 1);
    }

    public function below(): static
    {
        return new static($this->x, $this->y + 1);
    }
}

function findLowPoints(array $heights): iterable
{
    foreach ($heights as $r => $row) {
        foreach ($row as $c => $val) {
            $neighbors = array_filter([
                $heights[$r-1][$c] ?? null,
                $heights[$r][$c-1] ?? null,
                $heights[$r+1][$c] ?? null,
                $heights[$r][$c+1] ?? null,
            ], fn($v): bool => !is_null($v));
            if ($val < min($neighbors)) {
                yield ['x' => $r, 'y' => $c];
            }
        }
    }
}

function basin(array $point, array $grid): array
{
    return computeBasin([], $point, $grid);
}

function computeBasin(array $points, array $point, array $grid): array
{
    if (in_array($grid[$point['x']][$point['y']] ?? null, [9, null], true)) {
        return $points;
    }
    if (in_array($point, $points, true)) {
        return $points;
    }
    return pipe($points,
        append($point),
        fn(array $points): array => computeBasin($points, ['x' => $point['x'] - 1, 'y' => $point['y']], $grid),
        fn(array $points): array => computeBasin($points, ['x' => $point['x'] + 1, 'y' => $point['y']], $grid),
        fn(array $points): array => computeBasin($points, ['x' => $point['x'], 'y' => $point['y'] - 1], $grid),
        fn(array $points): array => computeBasin($points, ['x' => $point['x'], 'y' => $point['y'] + 1], $grid),
    );
}

function sortBySize(array $basins): array
{
    usort($basins, static fn ($a, $b): int => (count($a) <=> count($b)));
    return array_reverse($basins);
}

function atake(int $count): callable
{
    return static function (iterable $a) use ($count): array {
        $ret = [];
        foreach ($a as $k => $v) {
            if (--$count < 0) {
                break;
            }
            $ret[$k] = $v;
        }
        return $ret;
    };
}

function array_multiply(array $values): int|float
{
    $ret = 1;
    foreach ($values as $v) {
        $ret *= $v;
    }
    return $ret;
}

$grid = pipe($inputFile,
    lines(...),
    amap(compose(
        str_split(...),
        amap(intval(...)),
    ))
);

$basinProduct = pipe($grid,
    findLowPoints(...),
    collect(),
    amap(fn(array $point) => basin($point, $grid)),
    sortBySize(...),
    atake(3),
    amap(count(...)),
    array_multiply(...),
);


print "The basin product is $basinProduct\n";