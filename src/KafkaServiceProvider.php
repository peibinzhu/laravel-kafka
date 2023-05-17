<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use PeibinLaravel\Kafka\Consumer\Consumer as ConsumerInstance;
use PeibinLaravel\Kafka\Contracts\ConsumerInterface;
use PeibinLaravel\Kafka\Contracts\ProducerInterface;
use PeibinLaravel\Kafka\Listeners\AfterWorkerExitListener;
use PeibinLaravel\Kafka\Listeners\BeforeMainServerStartListener;
use PeibinLaravel\Kafka\Producer\Producer as ProducerInstance;
use PeibinLaravel\SwooleEvent\Events\BeforeMainServerStart;
use PeibinLaravel\SwooleEvent\Events\OnWorkerStop;

class KafkaServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $dependencies = [
            Producer::class          => Producer::class,
            ProducerInterface::class => ProducerInstance::class,
            ConsumerInterface::class => ConsumerInstance::class,
        ];
        $this->registerDependencies($dependencies);

        $listeners = [
            BeforeMainServerStart::class => [
                BeforeMainServerStartListener::class => 99,
            ],
            OnWorkerStop::class          => [
                AfterWorkerExitListener::class => 1,
            ],
        ];
        $this->registerListeners($listeners);

        $this->registerPublishing();
    }

    private function registerDependencies(array $dependencies)
    {
        $config = $this->app->get(Repository::class);
        foreach ($dependencies as $abstract => $concrete) {
            $concreteStr = is_string($concrete) ? $concrete : gettype($concrete);
            if (is_string($concrete) && method_exists($concrete, '__invoke')) {
                $concrete = function () use ($concrete) {
                    return $this->app->call($concrete . '@__invoke');
                };
            }
            $this->app->singleton($abstract, $concrete);
            $config->set(sprintf('dependencies.%s', $abstract), $concreteStr);
        }
    }

    private function registerListeners(array $listeners)
    {
        $dispatcher = $this->app->get(Dispatcher::class);
        foreach ($listeners as $event => $_listeners) {
            foreach ((array)$_listeners as $listener => $priority) {
                if (is_string($priority)) {
                    $listener = $priority;
                    $priority = 0;
                }
                $dispatcher->listen($event, $listener);
            }
        }
    }

    public function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/kafka.php' => config_path('kafka.php'),
            ], 'kafka');
        }
    }
}
