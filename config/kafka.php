<?php

declare(strict_types=1);

return [
    'default' => [
        'bootstrap_servers'      => [],
        // 未获取消息到消息时，延迟多少秒再次尝试（单位：秒，支持小数）
        'interval'               => 1.5,
        // rdkakfa配置
        'common_options'         => [
            'api.version.request' => true,
            'log_level'           => LOG_WARNING,
        ],
        'producer_options'       => [
            'retries'           => 3,
            'retry.backoff.ms'  => 1000,
            'message.max.bytes' => 1000000,
            'linger.ms'         => 10,
            // 生产者要求领导者，在确认请求完成之前已收到的确认数值。允许的值：0 表示无确认，1 表示仅领导者，-1 表示完整的 ISR。
            'acks'              => 1,
        ],
        'producer_topic_options' => [],
        'consumer_options'       => [
            'auto.offset.reset'         => 'latest',
            'enable.partition.eof'      => true,
            'heartbeat.interval.ms'     => 3000,
            'session.timeout.ms'        => 30000,
            'fetch.max.bytes'           => 1024000,
            'max.partition.fetch.bytes' => 256000,
        ],
        'consumer_topic_options' => [],
    ],
];
