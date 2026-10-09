<?php

namespace Prm\Moderation\Spam\Listeners;

class CheckBioContent extends AbstractProfileFieldCheck
{
    protected function attribute(): string
    {
        return 'bio';
    }

    protected function isFeatureEnabled(): bool
    {
        return $this->config->scanBios();
    }
}
