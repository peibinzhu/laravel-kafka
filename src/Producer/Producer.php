<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Producer;

use PeibinLaravel\Kafka\Contracts\ProducerInterface;
use RdKafka\Conf;
use RdKafka\Producer as RdKafkaProducer;
use RdKafka\ProducerTopic;
use RdKafka\TopicConf;

class Producer implements ProducerInterface
{
    protected RdKafkaProducer $producer;

    /**
     * @var ProducerTopic[]
     */
    protected array $topics;

    public function __construct(protected ProducerConfig $config)
    {
        $conf = new Conf();
        foreach ($config->getGlobalOptions() as $name => $value) {
            $conf->set($name, $value);
        }

        if ($bootstrapServers = $this->config->getBootstrapServers()) {
            $this->producer = new RdKafkaProducer($conf);
            $this->producer->addBrokers(implode(',', $bootstrapServers));
        }
    }

    public function send(
        string $topic,
        ?string $value,
        ?string $key = null,
        array $headers = [],
        ?int $partitionIndex = null
    ) {
        $message = new ProduceMessage($topic, $value, $key, $headers, $partitionIndex);
        $messages = [$message];
        $this->sendBatch($messages);
    }

    /**
     * @param ProduceMessage[] $messages
     */
    public function sendBatch(array $messages)
    {
        foreach ($messages as $message) {
            $producerTopic = $this->getProducerTopic($message->getTopic());
            $producerTopic->produce(
                $message->getPartitionIndex(),
                0,
                $message->getValue(),
                $message->getKey()
            );
            $this->producer->poll(0);
        }
    }

    public function close()
    {
        $this->topics = [];
        for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
            $result = $this->producer->flush(10000);
            if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                break;
            }
        }
    }

    protected function getProducerTopic(string $topicName): ProducerTopic
    {
        if (isset($this->topics[$topicName])) {
            return $this->topics[$topicName];
        }

        $conf = new TopicConf();
        foreach ($this->config->getTopicOptions() as $name => $value) {
            $conf->set($name, $value);
        }

        return $this->topics[$topicName] = $this->producer->newTopic($topicName, $conf);
    }
}
