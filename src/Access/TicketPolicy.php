<?php

namespace Prm\Moderation\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use Prm\Moderation\Ticket;

class TicketPolicy extends AbstractPolicy
{
    public function create(User $actor)
    {
        return $actor->hasPermission('moderation.ticket');
    }

    public function view(User $actor, Ticket $ticket)
    {
        if ($actor->hasPermission('moderation.access')) {
            return $this->allow();
        }

        if ((int) $actor->id === (int) $ticket->user_id) {
            return $this->allow();
        }
    }

    public function reply(User $actor, Ticket $ticket)
    {
        return $this->view($actor, $ticket);
    }

    public function close(User $actor, Ticket $ticket)
    {
        return $this->view($actor, $ticket);
    }

    public function reopen(User $actor, Ticket $ticket)
    {
        return $actor->hasPermission('moderation.access') ? $this->allow() : $this->deny();
    }
}
