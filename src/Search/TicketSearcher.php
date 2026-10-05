<?php

namespace Prm\Moderation\Search;

use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Prm\Moderation\Ticket;

class TicketSearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        $actor->assertRegistered();

        $query = Ticket::query();

        if (! $actor->hasPermission('moderation.access')) {
            $actor->assertPermission($actor->can('create', Ticket::class));
            $query->where('user_id', $actor->id);
        }

        return $query;
    }
}
