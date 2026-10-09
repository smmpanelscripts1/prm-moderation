<?php

namespace Prm\Moderation\Spam\Detectors;

use Flarum\User\User;
use Prm\Moderation\Spam\AbstractDetector;
use Prm\Moderation\Spam\SpamScore;

class BlockedWordsDetector extends AbstractDetector
{
    public function analyze(string $content, User $user, array $context = []): SpamScore
    {
        if (! $this->config->detectBlockedWords() || ! $this->shouldMonitorUser($user)) {
            return new SpamScore();
        }

        $words = $this->config->blockedWords();
        if ($words === []) {
            return new SpamScore();
        }

        $clean = $this->stripFormatting($content);
        $matched = [];
        $score = 0;

        $isProfile = ($context['type'] ?? '') === 'profile';

        foreach ($words as $word) {
            $escaped = preg_quote($word, '/');
            $escaped = preg_replace('/\s+/', '\\s+', $escaped) ?? $escaped;
            $pattern = '/\b'.$escaped.'\b/iu';

            $hit = (bool) @preg_match($pattern, $clean);

            // Usernames/nicknames are often one token (viagraSeller). For profile fields,
            // also allow substring matches on single-word blocklist entries.
            if (! $hit && $isProfile && ! preg_match('/\s/u', $word)) {
                $hit = (bool) @preg_match('/'.$escaped.'/iu', $clean);
            }

            if ($hit) {
                $matched[] = $word;
                $score += 50;
            }
        }

        if ($matched === []) {
            return new SpamScore();
        }

        return new SpamScore(
            min(100, $score),
            array_map(fn (string $w) => "Contains blocked word: {$w}", $matched),
            ['words' => $matched]
        );
    }

    public function getName(): string
    {
        return 'blocked_words';
    }
}
