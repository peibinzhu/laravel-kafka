<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Consumer;

class RecordHeader
{
    protected string $key = '';

    protected string $value = '';

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }
}
