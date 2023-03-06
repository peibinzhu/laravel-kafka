<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka;

use Illuminate\Support\ServiceProvider;
use PeibinLaravel\Kafka\Consumer\Consumer as ConsumerInstance;
use PeibinLaravel\Kafka\Contracts\ConsumerInterface;
use PeibinLaravel\Kafka\Contracts\ProducerInterface;
use PeibinLaravel\Kafka\Listeners\AfterWorkerExitListener;
use PeibinLaravel\Kafka\Listeners\BeforeMainServerStartListener;
use PeibinLaravel\Kafka\Producer\Producer as ProducerInstance;
use PeibinLaravel\ProviderConfig\Contracts\ProviderConfigInterface;
use PeibinLaravel\SwooleEvent\Events\BeforeMainServerStart;
use PeibinLaravel\SwooleEvent\Events\OnWorkerStop;

class KafkaServiceProvider extends ServiceProvider implements ProviderConfigInterface
{
    public function __invoke(): array
    {
        $this->app->bind(ProducerInterface::class, ProducerInstance::class);

        return [
            'dependencies' => [
                Producer::class          => Producer::class,
                ConsumerInterface::class => ConsumerInstance::class,
            ],
            'listeners'    => [
                BeforeMainServerStart::class => [
                    BeforeMainServerStartListener::class => 99,
                ],
                OnWorkerStop::class          => [
                    AfterWorkerExitListener::class,
                ],
            ],
            'publish'      => [
                [
                    'id'          => 'kafka',
                    'source'      => __DIR__ . '/../config/kafka.php',
                    'destination' => config_path('kafka.php'),
                ],
            ],
        ];
    }
}
