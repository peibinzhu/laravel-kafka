<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Events;

use PeibinLaravel\Kafka\AbstractConsumer;

class AfterConsume extends Consume
{
    public function __construct(AbstractConsumer $consumer, $data, protected ?string $result)
    {
        parent::__construct($consumer, $data);
    }

    public function getResult(): ?string
    {
        return $this->result;
    }
}
