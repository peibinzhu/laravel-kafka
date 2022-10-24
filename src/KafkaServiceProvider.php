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
use PeibinLaravel\SwooleEvent\Events\BeforeMainServerStart;
use PeibinLaravel\SwooleEvent\Events\OnWorkerStop;
use PeibinLaravel\Utils\Providers\RegisterProviderConfig;

class KafkaServiceProvider extends ServiceProvider
{
    use RegisterProviderConfig;

    public function __invoke(): array
    {
        $this->app->singleton(Producer::class);

        return [
            'dependencies' => [
                ProducerInterface::class => ProducerInstance::class,
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
