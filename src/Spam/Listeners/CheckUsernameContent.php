<?php

namespace Prm\Moderation\Spam\Listeners;

class CheckUsernameContent extends AbstractProfileFieldCheck
{
    protected function attribute(): string
    {
        return 'username';
    }

    protected function isFeatureEnabled(): bool
    {
        return $this->config->scanUsernames();
    }
}
