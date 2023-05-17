<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use PeibinLaravel\Coordinator\Constants;
use PeibinLaravel\Coordinator\CoordinatorManager;
use PeibinLaravel\Coroutine\Coroutine;
use PeibinLaravel\Engine\Channel;
use PeibinLaravel\Kafka\Contracts\ProducerInterface;
use PeibinLaravel\Kafka\Exceptions\ConnectionClosedException;
use PeibinLaravel\Kafka\Exceptions\TimeoutException;
use PeibinLaravel\Kafka\Producer\ProduceMessage;
use PeibinLaravel\Kafka\Producer\ProducerConfig;
use Throwable;

class Producer
{
    protected ?Channel $chan = null;

    private ?ProducerInterface $producer = null;

    public function __construct(
        protected Repository $config,
        protected string $name = 'default',
        protected int $timeout = 10
    ) {
    }

    /**
     * @param string      $topic
     * @param string|null $value
     * @param string|null $key
     * @param array       $headers
     * @param int|null    $partitionIndex
     * @return void
     */
    public function send(
        string $topic,
        ?string $value,
        ?string $key = null,
        array $headers = [],
        ?int $partitionIndex = null
    ): void {
        $this->loop();
        $ack = new Channel(1);
        $chan = $this->chan;
        $chan->push(function () use ($topic, $key, $value, $headers, $partitionIndex, $ack) {
            try {
                $this->producer->send($topic, $value, $key, $headers, $partitionIndex);
                $ack->close();
            } catch (Throwable $e) {
                $ack->push($e);
                throw $e;
            }
        });
        if ($chan->isClosing()) {
            throw new ConnectionClosedException('Connection closed.');
        }
        if ($e = $ack->pop($this->timeout)) {
            throw $e;
        }
        if ($ack->isTimeout()) {
            throw new TimeoutException('Kafka send timeout.');
        }
    }

    /**
     * @param ProduceMessage[] $messages
     * @return void
     */
    public function sendBatch(array $messages): void
    {
        $this->loop();
        $ack = new Channel(1);
        $chan = $this->chan;
        $chan->push(function () use ($messages, $ack) {
            try {
                $this->producer->sendBatch($messages);
                $ack->close();
            } catch (Throwable $e) {
                $ack->push($e);
                throw $e;
            }
        });
        if ($chan->isClosing()) {
            throw new ConnectionClosedException('Connection closed.');
        }
        if ($e = $ack->pop()) {
            throw $e;
        }
        if ($ack->isTimeout()) {
            throw new TimeoutException('Kafka send timeout.');
        }
    }

    public function close(): void
    {
        $this->chan?->close();
        $this->producer?->close();
    }

    protected function loop(): void
    {
        if ($this->chan != null) {
            return;
        }

        $this->chan = new Channel(1);
        Coroutine::create(function () {
            while (true) {
                $this->producer = $this->makeProducer();
                while (true) {
                    $closure = $this->chan?->pop();
                    if (!$closure) {
                        break 2;
                    }
                    try {
                        $closure->call($this);
                    } catch (Throwable) {
                        $this->producer?->close();
                        break;
                    }
                }
            }

            $this->chan?->close();
            $this->chan = null;
            $this->producer?->close();
        });

        Coroutine::create(function () {
            if (CoordinatorManager::until(Constants::WORKER_EXIT)->yield()) {
                $this->chan?->close();
            }
        });
    }

    private function makeProducer(): ProducerInterface
    {
        $config = $this->config->get('kafka.' . $this->name);
        $producerConfig = new ProducerConfig();
        $commonOptions = $config['common_options'] ?? [];
        $producerOptions = $config['producer_options'] ?? [];
        $producerConfig->setGlobalOptions(array_merge($commonOptions, $producerOptions));
        $producerConfig->setTopicOptions($config['producer_topic_options'] ?? []);
        $producerConfig->setBootstrapServers($config['bootstrap_servers']);
        $producerConfig->setAcks($config['acks'] ?? ($config['producer_options']['acks'] ?? 0));
        return Container::getInstance()->make(ProducerInterface::class, ['config' => $producerConfig]);
    }
}
