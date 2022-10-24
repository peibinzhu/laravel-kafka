<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Consumer;

use BadFunctionCallException;
use InvalidArgumentException;
use PeibinLaravel\Kafka\Contracts\ConsumerInterface;
use PeibinLaravel\Kafka\Exceptions\KafkaException;
use RdKafka\Conf;
use RdKafka\Exception;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

class Consumer implements ConsumerInterface
{
    /**
     * @var ConsumeMessage[]
     */
    private array $messages = [];

    private bool $started = false;

    private ConsumerConfig $config;

    /**
     * @var callable|null
     */
    private $consumeCallback;

    private KafkaConsumer $consumer;

    public function __construct(ConsumerConfig $config, ?callable $consumeCallback = null)
    {
        if (!extension_loaded('rdkafka')) {
            throw new BadFunctionCallException('not support: rdkafka');
        }

        $this->config = $config;
        $this->consumeCallback = $consumeCallback;
    }

    /**
     * @throws Exception
     */
    public function start(): void
    {
        $consumeCallback = $this->consumeCallback;
        if ($consumeCallback === null) {
            throw new InvalidArgumentException('consumeCallback must not null');
        }

        if (!$this->config->getAutoCommit()) {
            throw new InvalidArgumentException('Manual submission is not supported.');
        }

        $conf = new Conf();
        foreach ($this->config->getGlobalOptions() as $name => $value) {
            $conf->set($name, $value);
        }

        $conf->set('group.id', $this->config->getGroupId());

        $consumer = $this->consumer = new KafkaConsumer($conf);
        $consumer->subscribe($this->config->getTopic());

        $interval = (int)($this->config->getInterval() * 1000000);

        $this->started = true;
        while ($this->started) {
            $message = $consumer->consume(5 * 1000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $this->messages[] = $message;
                    $message = $this->consume();
                    $consumeCallback($message);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    $interval > 0 && usleep($interval);
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    break;
                default:
                    throw new KafkaException($message->errstr(), $message->err);
            }
        }
    }

    public function stop(): void
    {
        $this->started = false;
    }

    public function consume(): ?ConsumeMessage
    {
        /** @var Message $message */
        $message = array_shift($this->messages);
        if ($message) {
            $headers = [];
            foreach ($message->headers ?? [] as $key => $value) {
                $headers[] = (new RecordHeader())->setKey($key)->setValue($value);
            }

            $message = new ConsumeMessage(
                $this,
                $message->topic_name,
                $message->partition,
                $message->key,
                $message->payload,
                $headers
            );
        }
        return $message;
    }

    public function ack(ConsumeMessage $message)
    {
    }
}
