<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Consumer;

use BadFunctionCallException;
use InvalidArgumentException;
use longlang\phpkafka\Consumer\Assignor\RangeAssignor;
use longlang\phpkafka\Consumer\ConsumeMessage;
use longlang\phpkafka\Consumer\Consumer as LongLangConsumer;
use longlang\phpkafka\Consumer\ConsumerConfig;
use longlang\phpkafka\Exception\KafkaErrorException;
use longlang\phpkafka\Protocol\RecordBatch\RecordHeader;
use longlang\phpkafka\Sasl\SaslInterface;
use PeibinLaravel\Kafka\Exceptions\KafkaException;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\TopicConf;

class RdKafkaConsumer extends LongLangConsumer
{
    /**
     * @var ConsumeMessage[]
     */
    private $messages = [];

    /**
     * @var bool
     */
    private $started = false;

    public function __construct(ConsumerConfig $config, ?callable $consumeCallback = null)
    {
        if (!extension_loaded('rdkafka')) {
            throw new BadFunctionCallException('not support: rdkafka');
        }

        $this->config = $config;
        $this->consumeCallback = $consumeCallback;
    }

    public function start(): void
    {
        $consumeCallback = $this->consumeCallback;
        if (null === $consumeCallback) {
            throw new InvalidArgumentException('consumeCallback must not null');
        }

        if (!$this->config->getAutoCommit()) {
            throw new InvalidArgumentException('Manual submission is not supported.');
        }

        $conf = new Conf();

        if ($sasl = $this->getSaslInfo()) {
            $conf->set('sasl.mechanisms', $sasl['mechanisms']);
            $conf->set('sasl.username', $sasl['username']);
            $conf->set('sasl.password', $sasl['password']);
        }

        $ssl = $this->config->getSsl();
        if ($ssl->getOpen()) {
            $conf->set('security.protocol', 'SASL_SSL');
            $conf->set('ssl.ca.location', $ssl->getCertFile());
        }

        $strategyClass = $this->config->getPartitionAssignmentStrategy();
        $strategy = $strategyClass == RangeAssignor::class ? 'range' : 'roundrobin';
        $conf->set('partition.assignment.strategy', $strategy);

        $conf->set('api.version.request', 'true');
        $conf->set('group.id', $this->config->getGroupId());
        $conf->set('metadata.broker.list', implode(',', $this->config->getBootstrapServers()));

        $topicConf = new TopicConf();
        $conf->setDefaultTopicConf($topicConf);

        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe($this->config->getTopic());

        $interval = (int)($this->config->getInterval() * 1000000);
        $this->started = true;
        while ($this->started) {
            $message = $consumer->consume(10 * 1000);
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
                $headers[] = (new RecordHeader())->setHeaderKey($key)->setValue($value);
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

    private function getSaslInfo(): ?array
    {
        $config = $this->getConfig()->getSasl();
        if (empty($config['type'])) {
            return null;
        }

        $class = new $config['type']($this->getConfig());
        if (!$class instanceof SaslInterface) {
            return null;
        }

        if (empty($config['username']) || empty($config['password'])) {
            throw new KafkaErrorException('sasl not found auth info');
        }

        return [
            'mechanisms' => $class->getName(),
            'username'   => $config['username'],
            'password'   => $config['password'],
        ];
    }
}
