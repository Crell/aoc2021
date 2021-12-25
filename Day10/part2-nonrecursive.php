<?php

declare(strict_types=1);

use Crell\fp\Evolvable;
use function Crell\fp\collect;
use function Crell\fp\itfilter;
use function Crell\fp\itmap;
use function Crell\fp\pipe;
use function Crell\fp\reduce;
use function Crell\fp\reduceUntil;

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

enum Result
{
    case OK;
    case Corrupted;
    case Incomplete;
}

class Parse
{
    use Evolvable;

    public readonly string $badchar;
    public readonly array $stack;
    public readonly bool $done;

    public function __construct()
    {
        $this->stack = [];
        $this->done = false;
    }

    public function status(): Result
    {
        return match (true) {
            isset($this->badchar) => Result::Corrupted,
            empty($this->stack) => Result::OK,
            default => Result::Incomplete,
        };
    }
}

function parse(Parse $parse, string $next): Parse
{
    $head = $parse->stack[0] ?? null;

    return match ($next) {
        '{' => $parse->with(stack: ['}', ...$parse->stack]),
        '<' => $parse->with(stack: ['>', ...$parse->stack]),
        '(' => $parse->with(stack: [')', ...$parse->stack]),
        '[' => $parse->with(stack: [']', ...$parse->stack]),
        '}', '>', ')', ']' => $next === $head
            ? $parse->with(stack: array_slice($parse->stack, 1))
            : $parse->with(done: true, badchar: $next),
    };
}

function score(string $char): int
{
    return match ($char) {
        ')' => 3,
        ']' => 57,
        '}' => 1197,
        '>' => 25137,
    };
}

function scoreRemainingCharacter(int $score, string $char): int
{
    return $score * 5 + match ($char) {
        ')' => 1,
        ']' => 2,
        '}' => 3,
        '>' => 4,
    };
}

function scoreRemaining(array $stack): int
{
    return reduce(0, scoreRemainingCharacter(...))($stack);
}

function imSort(array $a): array
{
    sort($a);
    return $a;
}

$score = pipe($inputFile,
    lines(...),
    itmap(str_split(...)),
    itmap(reduceUntil(new Parse(), parse(...), fn (Parse $p): bool => $p->done)),
    itfilter(fn (Parse $p): bool => $p->status() === Result::Incomplete),
    itmap(fn (Parse $p): array => $p->stack),
    itmap(scoreRemaining(...)),
    collect(),
    imSort(...),
    fn(array $a) => $a[floor(count($a)/2)],
);

print "The total syntax error score is: $score\n";
