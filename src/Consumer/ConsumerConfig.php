<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Consumer;

use PeibinLaravel\Kafka\Config\CommonConfig;

class ConsumerConfig extends CommonConfig
{
    /**
     * @var string|string[]|null
     */
    protected $brokers;

    /**
     * @var float|null
     */
    protected $interval = 0;

    /**
     * @var string
     */
    protected $groupId = '';

    /**
     * @var string[]
     */
    protected $topic;

    /**
     * @var bool
     */
    protected $autoCommit = true;

    /**
     * @var bool
     */
    protected $autoCreateTopic = true;

    public function getInterval(): ?float
    {
        return $this->interval;
    }

    public function setInterval(float $interval): self
    {
        $this->interval = $interval;

        return $this;
    }

    public function getGroupId(): string
    {
        return $this->groupId;
    }

    public function setGroupId(string $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getTopic(): array
    {
        return $this->topic;
    }

    /**
     * @param string|string[] $topic
     */
    public function setTopic($topic): self
    {
        $this->topic = (array)$topic;

        return $this;
    }

    /**
     * @return string|string[]
     */
    public function getBrokers()
    {
        return $this->brokers;
    }

    /**
     * @param string|string[] $brokers
     *
     * @return $this
     */
    public function setBrokers($brokers): self
    {
        $this->brokers = $brokers;

        return $this;
    }

    public function getAutoCommit(): bool
    {
        return $this->autoCommit;
    }

    public function setAutoCommit(bool $autoCommit): self
    {
        $this->autoCommit = $autoCommit;

        return $this;
    }

    public function getAutoCreateTopic(): bool
    {
        return $this->autoCreateTopic;
    }

    public function setAutoCreateTopic(bool $autoCreateTopic): self
    {
        $this->autoCreateTopic = $autoCreateTopic;

        return $this;
    }
}
