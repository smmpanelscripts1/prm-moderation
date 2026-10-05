<?php

namespace Prm\Moderation\Search;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Prm\Moderation\Ticket;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class TicketStatusFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'status';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $status = $this->asString($value);
        $actor = $state->getActor();
        $query = $state->getQuery();

        if ($actor->hasPermission('moderation.access')) {
            if ($status === 'inbox') {
                $query->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_WAITING]);
            } elseif (in_array($status, [Ticket::STATUS_OPEN, Ticket::STATUS_WAITING, Ticket::STATUS_ANSWERED, Ticket::STATUS_CLOSED], true)) {
                $query->where('status', $status);
            }

            return;
        }

        if ($status === 'open') {
            $query->where('status', '!=', Ticket::STATUS_CLOSED);
        } elseif ($status === Ticket::STATUS_CLOSED) {
            $query->where('status', Ticket::STATUS_CLOSED);
        }
    }
}
