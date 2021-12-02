<?php

declare(strict_types=1);

use function Crell\fp\reduce;
use function Crell\fp\itmap;
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

enum Command: string
{
    case Forward = 'forward';
    case Up = 'up';
    case Down = 'down';
}

class Step
{
    public function __construct(
        public readonly Command $cmd,
        public readonly int $size,
    ) {}
}

function parse(string $line): Step
{
    [$cmd, $size] = \explode(' ', $line);
    return new Step(cmd: Command::from($cmd), size: (int)$size);
}

class Position
{
    public function __construct(
        public readonly int $distance,
        public readonly int $depth,
        public readonly int $aim,
    ) {}

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

function move(Position $p, Step $next): Position
{
    return match ($next->cmd) {
        Command::Down => $p->with(aim: $p->aim + $next->size),
        Command::Up => $p->with(aim: $p->aim - $next->size),
        Command::Forward => $p->with(
            distance: $p->distance + $next->size,
            depth: $p->depth + $p->aim * $next->size
        ),
    };
}

/** @var Position $end */
$end = pipe($inputFile,
    lines(...),
    itmap(parse(...)),
    reduce(new Position(0, 0, 0), move(...)),
);

print $end->distance * $end->depth . PHP_EOL;

