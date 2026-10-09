<?php

namespace Prm\Moderation\Spam\Detectors;

use Flarum\User\User;
use Prm\Moderation\Spam\AbstractDetector;
use Prm\Moderation\Spam\SpamScore;

class UrlDetector extends AbstractDetector
{
    private const URL_PATTERN = '~
        (?<! [\w@.\-] )
        (?:
            [a-z][a-z0-9+.\-]* :// [-\w]+ (?: \. [-\w]+ )*
          | // [-\w]+ (?: \. [-\w]+ )*
          | www \. [-\w]+ (?: \. [-\w]+ )*
          | (?: [a-z0-9] [-a-z0-9]* \. )+ (?: com|org|net|edu|gov|biz|info|xyz|top|online|site|shop|store|club|io|co|me|tv|cc|tk|ml|ga|cf|gq|ru|cn|uk|de|fr|tr|us|eu ) (?! [-\w] )
        )
    ~ixu';

    public function analyze(string $content, User $user, array $context = []): SpamScore
    {
        if (! $this->config->detectUrls() || ! $this->shouldMonitorUser($user)) {
            return new SpamScore();
        }

        $clean = $this->stripFormatting($content);

        if (! preg_match_all(self::URL_PATTERN, $clean, $matches)) {
            return new SpamScore();
        }

        $allowed = $this->config->allowedDomains();
        $flagged = [];

        foreach ($matches[0] as $url) {
            $host = $this->extractHost($url);
            if ($host === '' || ! $this->isAllowed($host, $allowed)) {
                $flagged[] = $url;
            }
        }

        if ($flagged === []) {
            return new SpamScore();
        }

        $count = count($flagged);

        return new SpamScore(
            min(80, $count * 50),
            [$count === 1
                ? 'Contains a URL to a non-allowlisted domain'
                : "Contains {$count} URLs to non-allowlisted domains"],
            ['urls' => array_values(array_unique($flagged))]
        );
    }

    public function getName(): string
    {
        return 'url';
    }

    /**
     * @param list<string> $allowed
     */
    protected function isAllowed(string $host, array $allowed): bool
    {
        $host = strtolower($host);

        foreach ($allowed as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }

    protected function extractHost(string $url): string
    {
        if (str_starts_with($url, '//')) {
            $url = 'http:'.$url;
        } elseif (! preg_match('~^[a-z][a-z0-9+.\-]*://~i', $url)) {
            $url = 'http://'.$url;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : '';
    }
}
