<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Listeners;

use Illuminate\Contracts\Container\Container;
use PeibinLaravel\Kafka\ConsumerManager;

class BeforeMainServerStartListener
{
    public function __construct(protected Container $container)
    {
    }

    public function handle(object $event): void
    {
        // Init the consumer process.
        $this->container->get(ConsumerManager::class)->run();
    }
}
