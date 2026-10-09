<?php

namespace Prm\Moderation\Spam;

use Flarum\User\User;
use Psr\Log\LoggerInterface;

class Analyzer
{
    /** @var list<DetectorInterface> */
    private array $detectors = [];

    public function __construct(
        private SpamConfig $config,
        private LoggerInterface $log
    ) {
    }

    public function addDetector(DetectorInterface $detector): self
    {
        $this->detectors[] = $detector;

        return $this;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function analyze(string $content, User $user, array $context = []): AnalysisResult
    {
        if (! $this->config->isEnabled()) {
            return new AnalysisResult(0, [], false, false, false, false);
        }

        $detectorScores = [];
        $totalScore = 0;

        foreach ($this->detectors as $detector) {
            try {
                if (! $detector->isEnabled()) {
                    continue;
                }

                $score = $detector->analyze($content, $user, $context);

                if ($score->isSpam()) {
                    $detectorScores[$detector->getName()] = $score;
                    $totalScore += $score->getScore();
                }
            } catch (\Throwable $e) {
                $this->log->error(
                    '[PRM Moderation] Spam detector error: '.$e->getMessage(),
                    ['detector' => get_class($detector)]
                );
            }
        }

        $totalScore = min(100, $totalScore);
        $flagThreshold = $this->config->flagThreshold();
        $spamThreshold = $this->config->spamThreshold();

        $isSpam = $totalScore >= $flagThreshold;
        $shouldFlag = $isSpam && $this->config->autoFlag();
        $shouldUnapprove = $totalScore >= $spamThreshold && $this->config->autoUnapprove();
        $shouldReport = $isSpam && $this->config->autoReport();

        return new AnalysisResult(
            $totalScore,
            $detectorScores,
            $isSpam,
            $shouldFlag,
            $shouldUnapprove,
            $shouldReport
        );
    }
}
