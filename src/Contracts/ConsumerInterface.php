<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Contracts;

interface ConsumerInterface
{
    public function start();

    public function stop();

    public function close();

    public function consume();
}
