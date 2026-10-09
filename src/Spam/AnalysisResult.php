<?php

namespace Prm\Moderation\Spam;

class AnalysisResult
{
    /**
     * @param array<string, SpamScore> $detectorScores
     */
    public function __construct(
        private int $totalScore,
        private array $detectorScores,
        private bool $isSpam,
        private bool $shouldFlag,
        private bool $shouldUnapprove,
        private bool $shouldReport
    ) {
    }

    public function getTotalScore(): int
    {
        return $this->totalScore;
    }

    public function isSpam(): bool
    {
        return $this->isSpam;
    }

    public function shouldFlag(): bool
    {
        return $this->shouldFlag;
    }

    public function shouldUnapprove(): bool
    {
        return $this->shouldUnapprove;
    }

    public function shouldReport(): bool
    {
        return $this->shouldReport;
    }

    /**
     * @return list<string>
     */
    public function getAllReasons(): array
    {
        $reasons = [];

        foreach ($this->detectorScores as $name => $score) {
            foreach ($score->getReasons() as $reason) {
                $reasons[] = "[{$name}] {$reason}";
            }
        }

        return $reasons;
    }
}
