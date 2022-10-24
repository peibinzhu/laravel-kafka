<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Consumer;

use PeibinLaravel\Kafka\Config\CommonConfig;

class ConsumerConfig extends CommonConfig
{
    protected string $groupId = '';

    /**
     * @var string[]
     */
    protected array $topic;

    protected bool $autoCommit = true;
    
    protected int | float $pollTime = 10;

    public function getGroupId(): string
    {
        return $this->groupId;
    }

    public function setGroupId(string $groupId): self
    {
        $this->groupId = $groupId;
        $this->setGlobalOption('group.id', $groupId);
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
    public function setTopic(array | string $topic): self
    {
        $this->topic = (array)$topic;

        return $this;
    }

    public function getAutoCommit(): bool
    {
        return $this->autoCommit;
    }

    public function setAutoCommit(bool $autoCommit): self
    {
        $this->autoCommit = $autoCommit;
        $this->setGlobalOption('enable.auto.commit', $autoCommit);
        return $this;
    }

    public function getPollTime(): float | int
    {
        return $this->pollTime;
    }

    public function setPollTime(float | int $pollTime): ConsumerConfig
    {
        $this->pollTime = $pollTime;
        return $this;
    }
}
