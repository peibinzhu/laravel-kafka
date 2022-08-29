<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Events;

use PeibinLaravel\Kafka\AbstractConsumer;

abstract class Event
{
    public function __construct(protected AbstractConsumer $consumer)
    {
    }

    public function getConsumer(): AbstractConsumer
    {
        return $this->consumer;
    }
}
