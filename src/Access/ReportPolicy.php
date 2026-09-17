<?php

namespace Prm\Moderation\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use Prm\Moderation\Report;

class ReportPolicy extends AbstractPolicy
{
    public function create(User $actor)
    {
        return $actor->hasPermission('moderation.report');
    }

    public function view(User $actor)
    {
        return $actor->hasPermission('moderation.access');
    }

    public function handle(User $actor, Report $report)
    {
        return $actor->hasPermission('moderation.access');
    }
}
