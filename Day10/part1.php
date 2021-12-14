<?php

declare(strict_types=1);

use function Crell\fp\afilter;
use function Crell\fp\amap;
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

enum Result
{
    case OK;
    case Corrupted;
    case Incomplete;
}

function parse(string $line, $pos = 0, array $stack = []): Result|string
{
    $next = $line[$pos] ?? null;
    $head = $stack[0] ?? null;

    return match ($next) {
        // Opening brace, push an "expected" onto the stack.
        '{' => parse($line, $pos + 1, ['}', ...$stack]),
        '<' => parse($line, $pos + 1, ['>', ...$stack]),
        '(' => parse($line, $pos + 1, [')', ...$stack]),
        '[' => parse($line, $pos + 1, [']', ...$stack]),
        '}', '>', ')', ']' => $next === $head ? parse($line, $pos + 1, array_slice($stack, 1)) : $next,
        null => count($stack) ? Result::Incomplete : Result::OK,
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

$score = pipe($inputFile,
    lines(...),
    amap(parse(...)),
    afilter(is_string(...)),
    amap(score(...)),
    array_sum(...),
);

print "The total syntax error score is: $score\n";