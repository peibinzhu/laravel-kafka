<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka;

use PeibinLaravel\Kafka\Consumer\ConsumeMessage;

abstract class AbstractConsumer
{
    public string $name = 'kafka';

    public string $pool = 'default';

    /**
     * @var string|string[]
     */
    public string | array $topic;

    public ?string $groupId = null;

    public bool $autoCommit = true;

    public function getPool(): string
    {
        return $this->pool;
    }

    public function setPool(string $pool): void
    {
        $this->pool = $pool;
    }

    public function getTopic(): array | string
    {
        return $this->topic;
    }

    public function setTopic($topic): void
    {
        $this->topic = $topic;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function setGroupId(?string $groupId): void
    {
        $this->groupId = $groupId;
    }

    public function isAutoCommit(): bool
    {
        return $this->autoCommit;
    }

    public function setAutoCommit(bool $autoCommit): void
    {
        $this->autoCommit = $autoCommit;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param ConsumeMessage $message
     * @return null|string
     */
    abstract public function consume(ConsumeMessage $message): ?string;
}
