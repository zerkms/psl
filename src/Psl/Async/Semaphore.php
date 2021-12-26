<?php

declare(strict_types=1);

namespace Psl\Async;

use Closure;
use Exception;
use Revolt\EventLoop\Suspension;

use function array_slice;

/**
 * Run an operation with a limit on number of ongoing asynchronous jobs.
 *
 * All operations must have the same input type (Tin) and output type (Tout), and be processed by the same function;
 * `Tin` may be a callable invoked by the `$operation` for maximum flexibility,
 * however this pattern is best avoided in favor of creating semaphores with a more narrow process.
 *
 * @template Tin
 * @template Tout
 */
final class Semaphore
{
    private int $pending = 0;

    /**
     * @var list<Suspension>
     */
    private array $suspensions = [];

    /**
     * @param positive-int $concurrencyLimit
     * @param (Closure(Tin): Tout) $operation
     */
    public function __construct(
        private int $concurrencyLimit,
        private Closure $operation,
    ) {
    }

    /**
     * Run the operation using the given `$input`.
     *
     * If the concurrency limit has been reached, this method will wait until one of the ingoing operations has completed.
     *
     * @param Tin $input
     *
     * @return Tout
     */
    public function waitFor(mixed $input): mixed
    {
        if ($this->pending === $this->concurrencyLimit) {
            $this->suspensions[] = $suspension = Scheduler::createSuspension();

            $suspension->suspend();
        }

        $this->pending++;

        try {
            return ($this->operation)($input);
        } finally {
            $suspension = $this->suspensions[0] ?? null;
            if ($suspension !== null) {
                $this->suspensions = array_slice($this->suspensions, 1);
                $suspension->resume();
            }

            $this->pending--;
        }
    }

    /**
     * Cancel all pending operations.
     *
     * Any pending operation will fail with the given exception.
     *
     * Future operations will continue execution as usual.
     */
    public function cancel(Exception $exception): void
    {
        $suspensions = $this->suspensions;
        $this->suspensions = [];
        foreach ($suspensions as $suspension) {
            $suspension->throw($exception);
        }
    }
}
