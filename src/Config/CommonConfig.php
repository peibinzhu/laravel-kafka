<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Config;

use InvalidArgumentException;

class CommonConfig
{
    /**
     * @var string[]
     */
    protected $bootstrapServers = [];

    protected array $globalOptions = [];

    protected array $topicOptions = [];

    /**
     * @param string|string[] $bootstrapServer
     */
    public function setBootstrapServer($bootstrapServer): self
    {
        return $this->setBootstrapServers($bootstrapServer);
    }

    public function getBootstrapServers(): array
    {
        return $this->bootstrapServers;
    }

    /**
     * @param string|string[] $bootstrapServers
     */
    public function setBootstrapServers(array | string $bootstrapServers): self
    {
        if (is_string($bootstrapServers)) {
            $this->bootstrapServers = explode(',', $bootstrapServers);
        } elseif (is_array($bootstrapServers)) {
            $this->bootstrapServers = $bootstrapServers;
        } else {
            throw new InvalidArgumentException(
                sprintf(
                    'The bootstrapServers must be string or array, and the current type is %s',
                    gettype($bootstrapServers)
                )
            );
        }

        $bootstrapServers = implode(',', $this->bootstrapServers);
        $this->globalOptions['metadata.broker.list'] = $bootstrapServers;
        return $this;
    }

    public function getGlobalOptions(): array
    {
        return $this->globalOptions;
    }

    public function setGlobalOptions(array $globalOptions): static
    {
        foreach ($globalOptions as $name => $value) {
            $this->globalOptions[(string)$name] = $this->transformConfValue($value);
        }
        return $this;
    }

    public function setGlobalOption(string $name, mixed $value): static
    {
        $this->globalOptions[$name] = $this->transformConfValue($value);
        return $this;
    }

    public function getTopicOptions(): array
    {
        return $this->topicOptions;
    }

    public function setTopicOptions(array $topicOptions): static
    {
        foreach ($topicOptions as $name => $value) {
            $this->topicOptions[(string)$name] = $this->transformConfValue($value);
        }
        return $this;
    }

    public function setTopicOption(string $name, mixed $value): static
    {
        $this->topicOptions[$name] = $this->transformConfValue($value);
        return $this;
    }

    private function transformConfValue($value): string
    {
        return is_bool($value)
            ? ($value === true ? 'true' : 'false')
            : (string)$value;
    }
}
