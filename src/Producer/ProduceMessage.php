<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Producer;

class ProduceMessage
{
    protected string $topic;

    protected ?string $value;

    protected ?string $key;

    protected array $headers;

    protected ?int $partitionIndex;

    public function __construct(
        string $topic,
        ?string $value,
        ?string $key = null,
        array $headers = [],
        ?int $partitionIndex = null
    ) {
        $this->topic = $topic;
        $this->value = $value;
        $this->key = $key;
        $this->headers = $headers;
        $this->partitionIndex = $partitionIndex ?? RD_KAFKA_PARTITION_UA;
    }

    public function getTopic(): string
    {
        return $this->topic;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getPartitionIndex(): ?int
    {
        return $this->partitionIndex;
    }
}
