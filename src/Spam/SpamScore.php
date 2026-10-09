<?php

namespace Prm\Moderation\Spam;

class SpamScore
{
    /**
     * @param list<string> $reasons
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private int $score = 0,
        private array $reasons = [],
        private array $metadata = []
    ) {
        $this->score = max(0, min(100, $score));
    }

    public function getScore(): int
    {
        return $this->score;
    }

    /**
     * @return list<string>
     */
    public function getReasons(): array
    {
        return $this->reasons;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isSpam(): bool
    {
        return $this->score > 0;
    }
}
