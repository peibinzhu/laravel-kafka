<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Listeners;

use Illuminate\Contracts\Container\Container;
use PeibinLaravel\Kafka\Producer;
use PeibinLaravel\Kafka\ProducerManager;

class AfterWorkerExitListener
{
    public function __construct(protected Container $container)
    {
    }

    public function handle(object $event): void
    {
        if ($this->container->has(Producer::class)) {
            $this->container->get(Producer::class)->close();
        }
        if ($this->container->has(ProducerManager::class)) {
            $this->container->get(ProducerManager::class)->closeAll();
        }
    }
}
