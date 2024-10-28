<?php

declare(strict_types=1);

namespace Psl\Type\Exception;

use Psl\Str;
use Psl\Vec;
use Throwable;

use function get_debug_type;

final class AssertException extends Exception
{
    private string $expected;

    /**
     * @param list<string> $paths
     */
    private function __construct(?string $actual, string $expected, string $message, array $paths = [], ?Throwable $previous = null)
    {
        parent::__construct(
            $message,
            $actual ?? 'null',
            $paths,
            $previous
        );

        $this->expected = $expected;
    }

    public function getExpectedType(): string
    {
        return $this->expected;
    }

    public static function withValue(
        mixed $value,
        string $expected_type,
        ?string $path = null,
        ?Throwable $previous = null
    ): self {
        $paths = Vec\filter_nulls($previous instanceof Exception ? [$path, ...$previous->getPaths()] : [$path]);
        $actual = get_debug_type($value);
        $first = $previous instanceof Exception ? $previous->getFirstFailingActualType() : $actual;

        $message = Str\format(
            'Expected "%s", got "%s"%s.',
            $expected_type,
            $first,
            $paths ? ' at path "' . Str\join($paths, '.') . '"' : '',
        );

        return new self($actual, $expected_type, $message, $paths, $previous);
    }

    public static function withoutValue(
        string $expected_type,
        ?string $path = null,
        ?Throwable $previous = null
    ): self {
        $paths = Vec\filter_nulls($previous instanceof Exception ? [$path, ...$previous->getPaths()] : [$path]);

        $message = Str\format(
            'Expected "%s", received no value at path "%s".',
            $expected_type,
            Str\join($paths, '.'),
        );

        return new self(null, $expected_type, $message, $paths, $previous);
    }
}
