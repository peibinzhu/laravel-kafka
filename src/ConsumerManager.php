<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use PeibinLaravel\Contracts\StdoutLoggerInterface;
use PeibinLaravel\Coordinator\Constants;
use PeibinLaravel\Coordinator\CoordinatorManager;
use PeibinLaravel\Coroutine\Coroutine;
use PeibinLaravel\Di\Annotation\AnnotationCollector;
use PeibinLaravel\Kafka\Annotations\Consumer as ConsumerAnnotation;
use PeibinLaravel\Kafka\Consumer\ConsumeMessage;
use PeibinLaravel\Kafka\Consumer\Consumer;
use PeibinLaravel\Kafka\Consumer\ConsumerConfig;
use PeibinLaravel\Kafka\Events\AfterConsume;
use PeibinLaravel\Kafka\Events\BeforeConsume;
use PeibinLaravel\Kafka\Events\FailToConsume;
use PeibinLaravel\Kafka\Exceptions\InvalidConsumeResultException;
use PeibinLaravel\Process\AbstractProcess;
use PeibinLaravel\Process\ProcessManager;

use function PeibinLaravel\Coroutine\wait;

class ConsumerManager
{
    public function __construct(protected Container $container)
    {
    }

    public function run(): void
    {
        $classes = AnnotationCollector::getClassesByAnnotation(ConsumerAnnotation::class);

        /**
         * @var string             $class
         * @var ConsumerAnnotation $annotation
         */
        foreach ($classes as $class => $annotation) {
            $instance = $this->container->make($class);
            if (!$instance instanceof AbstractConsumer || !$annotation->enable) {
                continue;
            }

            $annotation->pool && $instance->setPool($annotation->pool);
            $annotation->topic && $instance->setTopic($annotation->topic);
            $annotation->groupId && $instance->setGroupId($annotation->groupId);
            $instance->setAutoCommit($annotation->autoCommit);

            $process = $this->createProcess($instance);
            $process->name = $instance->getName();
            $process->nums = (int)$annotation->nums;
            ProcessManager::register($process);
        }
    }

    protected function createProcess(AbstractConsumer $consumer): AbstractProcess
    {
        return new class($this->container, $consumer) extends AbstractProcess {
            private AbstractConsumer $consumer;

            private Repository $config;

            private ?Dispatcher $dispatcher;

            protected StdoutLoggerInterface $stdoutLogger;

            protected Producer $producer;

            public function __construct(Container $container, AbstractConsumer $consumer)
            {
                parent::__construct($container);

                $this->consumer = $consumer;
                $this->config = $container->get(Repository::class);
                $this->stdoutLogger = $container->get(StdoutLoggerInterface::class);
                $this->producer = $container->get(Producer::class);
                if ($container->has(Dispatcher::class)) {
                    $this->dispatcher = $container->get(Dispatcher::class);
                }
            }

            public function handle(): void
            {
                $consumerConfig = $this->getConsumerConfig();
                $consumer = $this->consumer;
                $consumeCallback = function (ConsumeMessage $message) use ($consumer, $consumerConfig) {
                    $config = $this->getConfig();
                    wait(function () use ($consumer, $consumerConfig, $message) {
                        $this->dispatcher && $this->dispatcher->dispatch(new BeforeConsume($consumer, $message));

                        $result = $consumer->consume($message);

                        if (!$consumerConfig->getAutoCommit()) {
                            if (!is_string($result)) {
                                throw new InvalidConsumeResultException('The result is invalid.');
                            }

                            if ($result === Result::ACK) {
                                $message->getConsumer()->ack($message);
                            }

                            if ($result === Result::REQUEUE) {
                                $this->producer->send(
                                    $message->getTopic(),
                                    $message->getValue(),
                                    $message->getKey(),
                                    $message->getHeaders()
                                );
                            }
                        }

                        $this->dispatcher?->dispatch(new AfterConsume($consumer, $message, $result));
                    }, $config['consume_timeout'] ?? -1);
                };

                $kafkaConsumer = $this->container->make(Consumer::class, [
                    'config'          => $consumerConfig,
                    'consumeCallback' => $consumeCallback,
                ]);

                // stop consumer when worker exit
                Coroutine::create(function () use ($kafkaConsumer) {
                    CoordinatorManager::until(Constants::WORKER_EXIT)->yield();
                    $kafkaConsumer->stop();
                });

                while (true) {
                    try {
                        $kafkaConsumer->start();
                    } catch (Throwable $exception) {
                        $this->stdoutLogger->warning((string)$exception);
                        $this->dispatcher?->dispatch(new FailToConsume($this->consumer, [], $exception));
                    }

                    if (CoordinatorManager::until(Constants::WORKER_EXIT)->yield(10)) {
                        break;
                    }
                }

                $kafkaConsumer->close();
            }

            public function getConfig(): array
            {
                return $this->config->get('kafka.' . $this->consumer->getPool());
            }

            public function getConsumerConfig(): ConsumerConfig
            {
                $key = 'kafka.' . $this->consumer->getPool();
                $config = $this->config->get($key);

                // If "bootstrap_servers" is not configured and the configuration center is enabled,
                // it will wait in a loop until "bootstrap_servers" are obtained
                if (!$config['bootstrap_servers'] && $this->config->get('config_center.enable', false)) {
                    usleep(100 * 1000);

                    return $this->getConsumerConfig();
                }

                $config = $this->config->get($key);

                $consumerConfig = new ConsumerConfig();
                $commonOptions = $config['common_options'] ?? [];
                $consumerOptions = $config['consumer_options'] ?? [];
                $consumerConfig->setGlobalOptions(array_merge($commonOptions, $consumerOptions));
                $consumerConfig->setTopicOptions($config['consumer_topic_options'] ?? []);
                $consumerConfig->setBootstrapServer($config['bootstrap_servers']);
                $consumerConfig->setTopic($this->consumer->getTopic());
                $consumerConfig->setGroupId($this->consumer->getGroupId() ?? uniqid('laravel-kafka-'));
                $consumerConfig->setAutoCommit($this->consumer->isAutoCommit());
                $consumerConfig->setPollTime($config['consumer_poll_time']);
                return $consumerConfig;
            }
        };
    }
}
