<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Contracts;

interface ProducerInterface
{
    public function send(
        string $topic,
        ?string $value,
        ?string $key = null,
        array $headers = [],
        ?int $partitionIndex = null
    );

    public function sendBatch(array $messages);
}
