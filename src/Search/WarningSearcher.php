<?php

namespace Prm\Moderation\Search;

use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Prm\Moderation\Warning;

class WarningSearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        $actor->assertRegistered();

        return Warning::query();
    }
}
