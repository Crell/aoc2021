<?php

declare(strict_types=1);

use function Crell\fp\afilter;
use function Crell\fp\amap;
use function Crell\fp\explode;
use function Crell\fp\first;
use function Crell\fp\flatten;
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

class Game
{
    use Evolvable;

    public readonly int $lastPlay;

    public readonly bool $done;

    public readonly ?Board $winner;

    public function __construct(
        public readonly array $plays,
        /** Board[] */
        public readonly array $boards,
    ) {
        $this->winner = null;
        $this->lastPlay = -1;
    }

    public function done(): bool
    {
        return $this->winner !== null;
    }
}

class Board
{
    use Evolvable;

    /** @var bool[][] */
    protected readonly array $marked;

    const WinRow = [true, true, true, true, true];

    public readonly bool $won;

    public function __construct(
        public readonly array $numbers,
    ) {
        $line = array_fill(0, 5, false);
        $this->marked = array_fill(0, 5, $line);
        $this->won = false;
    }

    public function play(int $num): static
    {
        foreach ($this->numbers as $i => $line) {
            foreach ($line as $j => $cell) {
                if ($cell === $num) {
                    return $this->mark($i, $j);
                }
            }
        }
        return $this;
    }

    public function mark(int $i, int $j): static
    {
        $marked = $this->marked;
        $marked[$i][$j] = true;

        $won = $marked[$i] === self::WinRow || array_column($marked, $j) === self::WinRow;

        return $this->with(
            marked: $marked,
            won: $won,
        );
    }

    public function score(int $lastPlay): int
    {
//        $nums = flatten($this->numbers);
//        $marks = flatten($this->marked);

        $sum = pipe($this->marked,
            flatten(...),
            amap(static fn (bool $is): bool => !$is),
            fn ($marks) => array_combine(flatten($this->numbers), $marks),
            afilter(),
            array_keys(...),
            array_sum(...),
        );

        return $sum * $lastPlay;
    }
}

function parseInstructions(string $inputFile): Game
{
    $data = pipe($inputFile,
        file_get_contents(...),
        explode(PHP_EOL),
    );

    $plays = pipe($data[0],
        explode(','),
        amap(trim(...)),
        amap(intval(...)),
    );

    $boards = pipe($data,
        fn(array $data): array => array_slice($data, 1),
        extractBoardData(...),
        amap(makeBoard(...)),
    );

    return new Game($plays, $boards);
}

function makeBoard(array $numbers): Board
{
    return new Board($numbers);
}

function extractBoardData(array $input): array
{
    return pipe($input,
        afilter(),
        amap(explodeBoardLine(...)),
        fn(array $in): array => array_chunk($in, 5),
    );
}

function explodeBoardLine(string $line): array
{
    return pipe($line,
        trim(...),
        static fn ($line) => preg_split("/\s+/", $line),
        amap(intval(...)),
    );
}

function gameStep($game, $next): Game
{
    $markBoard = static fn (Board $board) => $board->play($next);
    $newBoards = array_map($markBoard, $game->boards);

    $winner = first(static fn (Board $b) => $b->won)($newBoards);

    return $game->with(
        lastPlay: $next,
        boards: $newBoards,
        winner: $winner,
    );
}

function reduceUntil(mixed $init, callable $c, callable $stop): callable
{
    return static function (iterable $it) use ($init, $c, $stop): mixed {
        foreach ($it as $v) {
            $init = $c($init, $v);
            if ($stop($init)) {
                return $init;
            }
        }
        return $init;
    };
}

$game = parseInstructions($inputFile);

$done = pipe($game->plays,
    reduceUntil($game, gameStep(...), static fn (Game $game): bool => (bool)$game->winner),
);

print $done->winner->score($done->lastPlay) . PHP_EOL;

//var_dump($done);
