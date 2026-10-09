<?php

namespace Prm\Moderation\Spam;

use Flarum\User\User;

abstract class AbstractDetector implements DetectorInterface
{
    private const MARKUP_TAG = '~</?[A-Za-z][A-Za-z0-9_-]*(?:\s[^<>]*)?/?>~';

    public function __construct(
        protected SpamConfig $config
    ) {
    }

    protected function shouldMonitorUser(User $user): bool
    {
        if ($user->isAdmin() || $user->can('discussion.hide') || $user->hasPermission('moderation.access')) {
            return false;
        }

        if ($user->isGuest()) {
            return true;
        }

        if ($this->config->monitorAllUsers()) {
            return true;
        }

        if ((int) $user->comment_count <= $this->config->monitorPostCount()) {
            return true;
        }

        if ($user->joined_at && $user->joined_at->diffInHours() <= $this->config->monitorHoursOld()) {
            return true;
        }

        return false;
    }

    protected function stripFormatting(string $content): string
    {
        $content = preg_replace(self::MARKUP_TAG, ' ', $content) ?? $content;
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = preg_replace('/\s+/', ' ', $content) ?? $content;

        return trim($content);
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }
}
