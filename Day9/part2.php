<?php

declare(strict_types=1);

use function Crell\fp\amap;
use function Crell\fp\append;
use function Crell\fp\collect;
use function Crell\fp\compose;
use function Crell\fp\pipe;

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

function findLowPoints(array $heights): iterable
{
    foreach ($heights as $r => $row) {
        foreach ($row as $c => $val) {
            $neighbors = array_filter([
                $heights[$r-1][$c] ?? null,
                $heights[$r][$c-1] ?? null,
                $heights[$r+1][$c] ?? null,
                $heights[$r][$c+1] ?? null,
            ], static fn ($v): bool => !is_null($v));
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

function computeBasin(array $basin, array $point, array $grid): array
{
    if (in_array($grid[$point['x']][$point['y']] ?? null, [9, null], true)) {
        return $basin;
    }
    if (in_array($point, $basin, true)) {
        return $basin;
    }
    return pipe($basin,
        append($point),
        fn(array $basin): array => computeBasin($basin, ['x' => $point['x'] - 1, 'y' => $point['y']], $grid),
        fn(array $basin): array => computeBasin($basin, ['x' => $point['x'] + 1, 'y' => $point['y']], $grid),
        fn(array $basin): array => computeBasin($basin, ['x' => $point['x'], 'y' => $point['y'] - 1], $grid),
        fn(array $basin): array => computeBasin($basin, ['x' => $point['x'], 'y' => $point['y'] + 1], $grid),
    );
}

function sortBySize(array $basins): array
{
    usort($basins, static fn ($a, $b): int => -1 * (count($a) <=> count($b)));
    return $basins;
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
    amap(fn(array $point) => basin($point, $grid)),
    sortBySize(...),
    atake(3),
    amap(count(...)),
    array_multiply(...),
);


print "The basin product is $basinProduct\n";
