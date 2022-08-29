<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka;

use Illuminate\Contracts\Container\Container;

class ProducerManager
{
    /**
     * @var array<string, Producer>
     */
    private array $producers = [];

    public function __construct(protected Container $container)
    {
    }

    public function getProducer(string $name = 'default'): Producer
    {
        if (isset($this->producers[$name])) {
            return $this->producers[$name];
        }
        $this->producers[$name] = $this->container->make(Producer::class, ['name' => $name]);
        return $this->producers[$name];
    }

    public function closeAll(): void
    {
        foreach ($this->producers as $producer) {
            $producer->close();
        }
    }
}
