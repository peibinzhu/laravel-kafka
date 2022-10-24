<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Producer;

use PeibinLaravel\Kafka\Config\CommonConfig;

class ProducerConfig extends CommonConfig
{
    protected string $topic;

    /**
     * The number of acknowledgments the producer requires the leader to have received before considering a request complete. Allowed values: 0 for no acknowledgments, 1 for only the leader and -1 for the full ISR.
     *
     * @var int
     */
    protected int $acks = 0;

    /**
     * @return string
     */
    public function getTopic(): string
    {
        return $this->topic;
    }

    /**
     * @param string $topic
     */
    public function setTopic(string $topic): void
    {
        $this->topic = $topic;
    }

    public function getAcks(): int
    {
        return $this->acks;
    }

    public function setAcks(int $acks): self
    {
        $this->acks = $acks;
        return $this;
    }
}
