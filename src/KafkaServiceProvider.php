<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka;

use Illuminate\Support\ServiceProvider;
use longlang\phpkafka\Consumer\Consumer as LongLangConsumer;
use PeibinLaravel\Kafka\Consumer\RdKafkaConsumer;
use PeibinLaravel\Kafka\Listeners\AfterWorkerExitListener;
use PeibinLaravel\Kafka\Listeners\BeforeMainServerStartListener;
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
                LongLangConsumer::class => RdKafkaConsumer::class,
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
