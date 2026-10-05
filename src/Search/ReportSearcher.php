<?php

namespace Prm\Moderation\Search;

use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Prm\Moderation\Report;

class ReportSearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        $actor->assertPermission($actor->hasPermission('moderation.access'));

        return Report::query();
    }
}
