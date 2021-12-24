<?php

declare(strict_types=1);

use function Crell\fp\collect;
use function Crell\fp\itfilter;
use function Crell\fp\itmap;
use function Crell\fp\pipe;
use function Crell\fp\reduce;

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

function parse(string $line, $pos = 0, array $stack = []): Result|string|array
{
    $next = $line[$pos] ?? null;
    $head = $stack[0] ?? null;

    return match ($next) {
        // Opening brace, push an "expected" onto the stack.
        '{' => parse($line, $pos + 1, ['}', ...$stack]),
        '<' => parse($line, $pos + 1, ['>', ...$stack]),
        '(' => parse($line, $pos + 1, [')', ...$stack]),
        '[' => parse($line, $pos + 1, [']', ...$stack]),
        // Pop a successful match off the stack if it matches, or return
        // a bad match.
        '}', '>', ')', ']' => $next === $head ? parse($line, $pos + 1, array_slice($stack, 1)) : $next,
        // If we run out of input, either return OK because we're done, or return the unmatched stack.
        null => count($stack) ? $stack : Result::OK,
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
    itmap(parse(...)),
    itfilter(is_array(...)),
    itmap(scoreRemaining(...)),
    collect(),
    imSort(...),
    fn(array $a) => $a[floor(count($a)/2)],
);

print "The total syntax error score is: $score\n";
