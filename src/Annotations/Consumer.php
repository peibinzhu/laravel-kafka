<?php

declare(strict_types=1);

namespace PeibinLaravel\Kafka\Annotations;

use Attribute;
use PeibinLaravel\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS)]
class Consumer extends AbstractAnnotation
{
    /**
     * @param string       $pool
     * @param array|string $topic
     * @param string|null  $groupId
     * @param string|null  $memberId
     * @param bool         $autoCommit
     * @param int          $nums
     * @param bool         $enable
     */
    public function __construct(
        public string $pool = 'default',
        public array | string $topic = '',
        public ?string $groupId = null,
        public ?string $memberId = null,
        public bool $autoCommit = true,
        public int $nums = 1,
        public bool $enable = true
    ) {
    }
}
