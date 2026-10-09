<?php

namespace Prm\Moderation\Spam\Detectors;

use Flarum\User\User;
use Prm\Moderation\Spam\AbstractDetector;
use Prm\Moderation\Spam\SpamScore;

class EmailDetector extends AbstractDetector
{
    private const EMAIL_PATTERN = '~\S+@\S+\.\S+~';

    public function analyze(string $content, User $user, array $context = []): SpamScore
    {
        if (! $this->config->detectEmails() || ! $this->shouldMonitorUser($user)) {
            return new SpamScore();
        }

        $clean = $this->stripFormatting($content);
        $matches = [];
        $count = preg_match_all(self::EMAIL_PATTERN, $clean, $matches);

        if (! $count) {
            return new SpamScore();
        }

        return new SpamScore(
            min(80, $count * 50),
            [$count === 1 ? 'Contains an email address' : "Contains {$count} email addresses"],
            ['count' => $count, 'emails' => array_values(array_unique($matches[0]))]
        );
    }

    public function getName(): string
    {
        return 'email';
    }
}
