<?php

namespace Prm\Moderation\Spam\Detectors;

use Flarum\User\User;
use Prm\Moderation\Spam\AbstractDetector;
use Prm\Moderation\Spam\SpamScore;

class PhoneDetector extends AbstractDetector
{
    private const PHONE_PATTERN = '/(\\+|00)([0-9-\p{No}\p{Nd}\p{M}\s]){9,}/u';

    public function analyze(string $content, User $user, array $context = []): SpamScore
    {
        if (! $this->config->detectPhones() || ! $this->shouldMonitorUser($user)) {
            return new SpamScore();
        }

        $clean = $this->stripFormatting($content);
        $matches = [];
        $count = preg_match_all(self::PHONE_PATTERN, $clean, $matches);

        if (! $count) {
            return new SpamScore();
        }

        return new SpamScore(
            min(80, $count * 50),
            [$count === 1 ? 'Contains a phone number' : "Contains {$count} phone numbers"],
            ['count' => $count, 'phones' => array_values(array_unique($matches[0]))]
        );
    }

    public function getName(): string
    {
        return 'phone';
    }
}
