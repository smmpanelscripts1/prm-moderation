<?php

namespace Prm\Moderation\Spam;

use Flarum\User\User;

interface DetectorInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function analyze(string $content, User $user, array $context = []): SpamScore;

    public function getName(): string;

    public function isEnabled(): bool;
}
