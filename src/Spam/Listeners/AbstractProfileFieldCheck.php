<?php

namespace Prm\Moderation\Spam\Listeners;

use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
use Flarum\User\Event\Saving;
use Flarum\User\User;
use Prm\Moderation\Spam\AnalysisResult;
use Prm\Moderation\Spam\Analyzer;
use Prm\Moderation\Spam\SpamConfig;
use Psr\Log\LoggerInterface;

/**
 * Profile fields have no approval queue — refuse the save above the spam threshold.
 */
abstract class AbstractProfileFieldCheck
{
    public function __construct(
        protected Analyzer $analyzer,
        protected SpamConfig $config,
        protected LoggerInterface $log,
        protected TranslatorInterface $translator
    ) {
    }

    abstract protected function attribute(): string;

    abstract protected function isFeatureEnabled(): bool;

    public function handle(Saving $event): void
    {
        if (! $this->config->isEnabled() || ! $this->isFeatureEnabled()) {
            return;
        }

        $user = $event->user;
        $attribute = $this->attribute();
        $actor = $event->actor;

        if ($this->isStaff($actor)) {
            return;
        }

        if (! $user->isDirty($attribute)) {
            return;
        }

        $value = (string) ($user->getAttribute($attribute) ?? '');

        if (trim($value) === '') {
            return;
        }

        $result = $this->analyzer->analyze($value, $user, [
            'type' => 'profile',
            'field' => $attribute,
            'user_id' => $user->id,
        ]);

        if (! $this->shouldReject($result)) {
            return;
        }

        $this->log->info(
            "[PRM Moderation] Spam indicators in {$attribute} for {$user->username}",
            [
                'score' => $result->getTotalScore(),
                'reasons' => $result->getAllReasons(),
                'field' => $attribute,
                'user_id' => $user->id,
            ]
        );

        throw new ValidationException([
            $attribute => $this->translator->trans('prm-moderation.forum.spam.profile_blocked'),
        ]);
    }

    protected function shouldReject(AnalysisResult $result): bool
    {
        return $result->shouldUnapprove();
    }

    protected function isStaff(User $actor): bool
    {
        return $actor->isAdmin()
            || $actor->can('discussion.hide')
            || $actor->hasPermission('moderation.access');
    }
}
