<?php

declare(strict_types=1);

namespace Psl\Type\Exception;

use Psl\Str;
use Psl\Vec;
use Throwable;

use function get_debug_type;

final class CoercionException extends Exception
{
    private string $target;

    /**
     * @param list<string> $paths
     */
    private function __construct(?string $actual, string $target, string $message, array $paths = [], ?Throwable $previous = null)
    {
        parent::__construct(
            $message,
            $actual ?? 'null',
            $paths,
            $previous
        );

        $this->target = $target;
    }

    public function getTargetType(): string
    {
        return $this->target;
    }

    public static function withValue(
        mixed $value,
        string $target,
        ?string $path = null,
        ?Throwable $previous = null
    ): self {
        $paths = Vec\filter_nulls($previous instanceof Exception ? [$path, ...$previous->getPaths()] : [$path]);
        $actual = get_debug_type($value);
        $first = $previous instanceof Exception ? $previous->getFirstFailingActualType() : $actual;

        $message = Str\format(
            'Could not coerce "%s" to type "%s"%s%s.',
            $first,
            $target,
            $paths ? ' at path "' . Str\join($paths, '.') . '"' : '',
            $previous && !$previous instanceof self ? ': ' . $previous->getMessage() : '',
        );

        return new self($actual, $target, $message, $paths, $previous);
    }

    public static function withoutValue(
        string $target,
        ?string $path = null,
        ?Throwable $previous = null
    ): self {
        $paths = Vec\filter_nulls($previous instanceof Exception ? [$path, ...$previous->getPaths()] : [$path]);

        $message = Str\format(
            'Could not coerce to type "%s" at path "%s" as the value was not passed%s.',
            $target,
            Str\join($paths, '.'),
            $previous && !$previous instanceof self ? ': ' . $previous->getMessage() : '',
        );

        return new self(null, $target, $message, $paths, $previous);
    }
}
