<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Events;

use PeibinLaravel\Kafka\AbstractConsumer;

abstract class Consume extends Event
{
    public function __construct(AbstractConsumer $consumer, protected mixed $data)
    {
        parent::__construct($consumer);
    }

    public function getData(): mixed
    {
        return $this->data;
    }
}
