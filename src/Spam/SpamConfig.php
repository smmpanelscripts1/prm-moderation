<?php

namespace Prm\Moderation\Spam;

use Flarum\Foundation\Config;
use Flarum\Settings\SettingsRepositoryInterface;

class SpamConfig
{
    private const PREFIX = 'prm-moderation.spam.';

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Config $config
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->bool('enabled', true);
    }

    public function monitorAllUsers(): bool
    {
        return $this->bool('monitor_all_users', false);
    }

    public function monitorPostCount(): int
    {
        return max(0, (int) $this->settings->get(self::PREFIX.'monitor_post_count', 5));
    }

    public function monitorHoursOld(): int
    {
        return max(0, (int) $this->settings->get(self::PREFIX.'monitor_hours_old', 24));
    }

    public function detectPhones(): bool
    {
        return $this->bool('detect_phones', true);
    }

    public function detectEmails(): bool
    {
        return $this->bool('detect_emails', true);
    }

    public function detectUrls(): bool
    {
        return $this->bool('detect_urls', true);
    }

    public function detectBlockedWords(): bool
    {
        return $this->bool('detect_blocked_words', true);
    }

    public function scanUsernames(): bool
    {
        return $this->bool('scan_usernames', true);
    }

    public function scanNicknames(): bool
    {
        return $this->bool('scan_nicknames', true);
    }

    public function scanBios(): bool
    {
        return $this->bool('scan_bios', true);
    }

    public function flagThreshold(): int
    {
        return max(0, (int) $this->settings->get(self::PREFIX.'flag_threshold', 30));
    }

    public function spamThreshold(): int
    {
        return max(0, (int) $this->settings->get(self::PREFIX.'spam_threshold', 50));
    }

    public function autoFlag(): bool
    {
        return $this->bool('auto_flag', true);
    }

    public function autoUnapprove(): bool
    {
        return $this->bool('auto_unapprove', true);
    }

    public function autoReport(): bool
    {
        return $this->bool('auto_report', true);
    }

    public function systemUserId(): int
    {
        return max(1, (int) $this->settings->get(self::PREFIX.'system_user_id', 1));
    }

    public function hidePostsOnMark(): bool
    {
        return $this->bool('hide_posts_on_mark', true);
    }

    public function hideDiscussionsOnMark(): bool
    {
        return $this->bool('hide_discussions_on_mark', true);
    }

    public function suspendOnMark(): bool
    {
        return $this->bool('suspend_on_mark', true);
    }

    /**
     * @return list<string>
     */
    public function allowedDomains(): array
    {
        $raw = (string) $this->settings->get(self::PREFIX.'allowed_domains', '');
        $domains = [];

        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $domain = $this->normalizeDomain($line);
            if ($domain !== '') {
                $domains[] = $domain;
            }
        }

        $forumHost = $this->config->url()->getHost();
        if ($forumHost) {
            $domains[] = $this->normalizeDomain($forumHost);
        }

        return array_values(array_unique(array_filter($domains)));
    }

    /**
     * @return list<string>
     */
    public function blockedWords(): array
    {
        $raw = (string) $this->settings->get(self::PREFIX.'blocked_words', '');

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: [])));
    }

    public function normalizeDomain(string $domain): string
    {
        $domain = preg_replace('~^https?://~i', '', trim($domain)) ?? '';
        $domain = explode('/', $domain)[0];
        $domain = explode(':', $domain)[0];

        return strtolower(trim($domain));
    }

    protected function bool(string $key, bool $default): bool
    {
        $value = $this->settings->get(self::PREFIX.$key, $default ? '1' : '0');

        return $value === '1' || $value === 1 || $value === true;
    }
}
