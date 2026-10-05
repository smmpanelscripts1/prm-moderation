<?php

namespace Prm\Moderation\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use Prm\Moderation\Warning;

class WarningPolicy extends AbstractPolicy
{
    public function create(User $actor, $model = null)
    {
        return $actor->hasPermission('moderation.access');
    }

    public function view(User $actor, Warning $warning)
    {
        return $actor->id === $warning->user_id || $actor->hasPermission('moderation.access');
    }
}
