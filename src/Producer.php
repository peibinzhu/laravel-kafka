<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka;

use Illuminate\Config\Repository;
use longlang\phpkafka\Producer\ProduceMessage;
use longlang\phpkafka\Producer\Producer as LongLangProducer;
use longlang\phpkafka\Producer\ProducerConfig;
use longlang\phpkafka\Socket\SwooleSocket;
use PeibinLaravel\Context\Context;

class Producer
{
    private ?LongLangProducer $producer;

    public function __construct(protected Repository $config, protected string $name = 'default')
    {
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
        $this->connection();
        $this->producer->send($topic, $value, $key, $headers, $partitionIndex);
    }

    /**
     * @param ProduceMessage[] $messages
     * @return void
     */
    public function sendBatch(array $messages): void
    {
        $this->connection();
        $this->producer->sendBatch($messages);
    }

    public function close(): void
    {
        $this->producer?->close();
    }

    private function connection(): void
    {
        $connections = [];
        if (Context::has('kafka')) {
            $connections = Context::get('kafka');
        }

        if (isset($connections[$this->name])) {
            $this->producer = $connections[$this->name];
            return;
        }

        $this->producer = $connections[$this->name] = $this->makeProducer();
        Context::set('kafka', $connections);
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
