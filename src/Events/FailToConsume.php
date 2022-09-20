<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Events;

use PeibinLaravel\Kafka\AbstractConsumer;
use Throwable;

class FailToConsume extends Consume
{
    public function __construct(AbstractConsumer $consumer, $data, protected Throwable $throwable)
    {
        parent::__construct($consumer, $data);
    }

    public function getThrowable(): Throwable
    {
        return $this->throwable;
    }
}
