<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerBridge\Queue;

use Psr\Container\ContainerInterface;
use Spiral\Boot\DispatcherInterface;
use Spiral\Boot\FinalizerInterface;
use Spiral\RoadRunner\Jobs\ConsumerInterface;
use Spiral\RoadRunner\Jobs\Exception\JobsException;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;
use Spiral\RoadRunnerBridge\RoadRunnerMode;
use Spiral\Queue\Interceptor\Handler;

final class Dispatcher implements DispatcherInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly FinalizerInterface $finalizer,
        private readonly RoadRunnerMode $mode
    ) {
    }

    public function canServe(): bool
    {
        return \PHP_SAPI === 'cli' && $this->mode === RoadRunnerMode::Jobs;
    }

    /**
     * @throws JobsException
     */
    public function serve(): void
    {
        /** @var ConsumerInterface $consumer */
        $consumer = $this->container->get(ConsumerInterface::class);

        /** @var Handler $handler */
        $handler = $this->container->get(Handler::class);

        while ($task = $consumer->waitTask()) {
            try {
                $handler->handle(
                    name: $task->getName(),
                    driver: 'roadrunner',
                    queue: $task->getQueue(),
                    id: $task->getId(),
                    payload: $task->getPayload()
                );

                $task->complete();
            } catch (\Throwable $e) {
                $this->handleException($task, $e);
            } finally {
                $this->finalizer->finalize(false);
            }
        }
    }

    protected function handleException(?ReceivedTaskInterface $task, \Throwable $e): void
    {
        $task->fail($e);
    }
}
