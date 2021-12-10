<?php

declare(strict_types=1);

use function Crell\fp\amap;
use function Crell\fp\afilter;
use function Crell\fp\reduce;
use function Crell\fp\compose;
use function Crell\fp\explode;
use function Crell\fp\pipe;
use function Crell\fp\amapWithKeys;

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

trait Evolvable
{
    public function with(...$values): static
    {
        $clone = (new \ReflectionClass(static::class))->newInstanceWithoutConstructor();

        foreach ($this as $field => $value) {
            $value = array_key_exists($field, $values) ? $values[$field] : $value;
            $clone->$field = $value;
        }

        return $clone;
    }
}

class Line
{
    use Evolvable;

    public readonly array $mapping;

    public readonly array $inputMasks;

    public function __construct(
        public readonly array $in,
        public readonly array $test,
    ) {
        $this->mapping = [];
        $range = range(2, 7);
        $this->inputMasks = array_combine(
            $range,
            array_map($this->makeMapList(...), $range)
        );
    }

    protected function makeMapList(int $segmentCount): array
    {
        return pipe($this->in,
            afilter(fn(string $s): bool => strlen($s) === $segmentCount),
            amap(strToBitmap(...)),
            fn(array $map): array => array_combine($map, $map),
        );
    }

    public static function fromInput(array $args): static
    {
        return new static($args[0], $args[1]);
    }
}

function parseLine(string $line): Line
{
    return pipe($line,
        explode(' | '),
        amap(compose(
            explode(' '),
        )),
        Line::fromInput(...),
    );
}

/**
 * Maps from the number of segments lit to the number that it could represent.
 */
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

function findByMask(array $masks, int $xorWith, int $remainder): int
{
    return pipe($masks,
        amap(compose(
            fn(int $mask): int => $mask ^ $xorWith,
            countBits(...),
        )),
        afilter(fn(int $count): bool => $count === $remainder),
        key(...),
    );
}

function deriveMapping(Line $line): Line
{
    // These all have only one possible translation, so do the easy bits.
    $mappings[1] = key($line->inputMasks[2]);
    $mappings[7] = key($line->inputMasks[3]);
    $mappings[4] = key($line->inputMasks[4]);
    $mappings[8] = key($line->inputMasks[7]);

    // these are still not quite right. In the second dataset, 3 and 9 are both coming back the same(!)

    $mappings[6] = findByMask($line->inputMasks[6], $mappings[1], 6);
    $mappings[2] = findByMask($line->inputMasks[5], $mappings[4], 5);
    $mappings[3] = findByMask($line->inputMasks[5], $mappings[1], 3);
    $mappings[5] = findByMask($line->inputMasks[5], $mappings[6], 1);
    $mappings[9] = findByMask($line->inputMasks[6], $mappings[3], 1);

    $mappings[0] = key(array_diff($line->inputMasks[6], $mappings));

//    $mappings[6] = pipe($line->inputMasks[6],
//        amap(compose(
//            fn(int $mask): int => $mask ^ $mappings[1],
//            countBits(...),
//        )),
//        afilter(fn(int $count): bool => $count === 6),
//        key(...),
//    );

    return $line->with(mapping: array_flip($mappings));
}

function countBits(int $n): int
{
    $count = 0;
    while ($n)
    {
        $count += $n & 1;
        $n >>= 1;
    }
    return $count;
}

function strToBitmap(string $s): int
{
    return pipe($s,
        str_split(...),
        reduce(0, static fn(int $mask, string $letter): int => $mask | letterToMask($letter) ),
    );
}

function letterToMask(string $l): int
{
    return match($l) {
        'a' => 2**0,
        'b' => 2**1,
        'c' => 2**2,
        'd' => 2**3,
        'e' => 2**4,
        'f' => 2**5,
        'g' => 2**6,
    };
}

function decode(Line $line): array
{
    return pipe($line->test,
        amap(strToBitmap(...)),
        amap(fn(int $test): int => $line->mapping[$test]),
    );
}

function digitsToNumber(array $digits): int
{
    return pipe($digits,
        array_reverse(...),
        amapWithKeys(static fn (int $v, int $k): int => $v * 10**$k),
        array_sum(...),
    );
}

$results = pipe($inputFile,
    lines(...),
    amap(compose(
        parseLine(...),
        deriveMapping(...),
        decode(...),
        digitsToNumber(...),
    )),
    array_sum(...),
);

print "The total number sum is: $results\n";
