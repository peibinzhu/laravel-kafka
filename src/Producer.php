<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka;

use Illuminate\Config\Repository;
use longlang\phpkafka\Producer\ProduceMessage;
use longlang\phpkafka\Producer\Producer as LongLangProducer;
use longlang\phpkafka\Producer\ProducerConfig;
use longlang\phpkafka\Socket\SwooleSocket;
use PeibinLaravel\Coordinator\Channel;
use PeibinLaravel\Kafka\Exceptions\ConnectionClosedException;
use PeibinLaravel\Kafka\Exceptions\TimeoutException;
use Swoole\Coroutine;
use Throwable;

class Producer
{
    protected ?Channel $chan = null;

    private ?LongLangProducer $producer = null;

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
                    $closure = $this->chan->pop();
                    if (!$closure) {
                        break 2;
                    }
                    try {
                        $closure->call($this);
                    } catch (Throwable) {
                        $this->producer->close();
                        break;
                    }
                }
            }

            $this->chan->close();
            $this->chan = null;
        });
    }

    private function makeProducer(): LongLangProducer
    {
        $config = $this->config->get('kafka.' . $this->name);

        $producerConfig = new ProducerConfig();
        $producerConfig->setConnectTimeout($config['connect_timeout']);
        $producerConfig->setSendTimeout($config['send_timeout']);
        $producerConfig->setRecvTimeout($config['recv_timeout']);
        $producerConfig->setClientId($config['client_id']);
        $producerConfig->setMaxWriteAttempts($config['max_write_attempts']);
        $producerConfig->setSocket(SwooleSocket::class);
        $producerConfig->setBootstrapServers($config['bootstrap_servers']);
        $producerConfig->setAcks($config['acks']);
        $producerConfig->setProducerId($config['producer_id']);
        $producerConfig->setProducerEpoch($config['producer_epoch']);
        $producerConfig->setPartitionLeaderEpoch($config['partition_leader_epoch']);
        $producerConfig->setAutoCreateTopic($config['auto_create_topic']);
        !empty($config['sasl']) && $producerConfig->setSasl($config['sasl']);
        !empty($config['ssl']) && $producerConfig->setSsl($config['ssl']);
        return new LongLangProducer($producerConfig);
    }
}
