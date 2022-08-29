<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Constants;

use longlang\phpkafka\Consumer\Assignor\RangeAssignor;
use longlang\phpkafka\Consumer\Assignor\RoundRobinAssignor;

class KafkaStrategy
{
    public const RANGE_ASSIGNOR = RangeAssignor::class;

    public const ROUND_ROBIN_ASSIGNOR = RoundRobinAssignor::class;
}
