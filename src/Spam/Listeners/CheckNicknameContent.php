<?php

namespace Prm\Moderation\Spam\Listeners;

class CheckNicknameContent extends AbstractProfileFieldCheck
{
    protected function attribute(): string
    {
        return 'nickname';
    }

    protected function isFeatureEnabled(): bool
    {
        return $this->config->scanNicknames();
    }
}
