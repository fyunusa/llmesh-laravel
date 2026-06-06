<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Events;

use Illuminate\Contracts\Events\Dispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Bridges the PSR-14 `EventDispatcherInterface` to Laravel's event system.
 *
 * This allows `LLMesh\Core\LLMesh` (which uses PSR-14 internally) to fire
 * events through Laravel's standard `Illuminate\Events\Dispatcher`, making
 * core LLMesh events observable via Laravel listeners and Horizon/Telescope.
 */
final class LaravelEventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
    ) {
    }

    /**
     * Dispatch an event.
     *
     * The event object is passed directly to Laravel's `event()` helper so
     * registered listeners will be invoked.
     *
     * @param  object $event PSR-14 event (any `LLMesh\Core\Events\*` object)
     * @return object        The same event (PSR-14 contract)
     */
    public function dispatch(object $event): object
    {
        $this->dispatcher->dispatch($event);

        return $event;
    }
}
