<?php

namespace Prm\Moderation\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;
use InvalidArgumentException;
use Prm\Moderation\Ticket;

class TicketSerializer extends AbstractSerializer
{
    protected $type = 'moderation-tickets';

    protected function getDefaultAttributes($ticket)
    {
        if (! ($ticket instanceof Ticket)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Ticket::class
            );
        }

        $actor = $this->getActor();

        return [
            'subject' => $ticket->subject,
            'category' => $ticket->category,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'createdAt' => $this->formatDate($ticket->created_at),
            'lastRepliedAt' => $this->formatDate($ticket->last_replied_at),
            'closedAt' => $this->formatDate($ticket->closed_at),
            'canReply' => $actor->can('reply', $ticket) && ! $ticket->isClosed(),
            'canClose' => $actor->can('close', $ticket) && ! $ticket->isClosed(),
            'canReopen' => $actor->can('reopen', $ticket) && $ticket->isClosed(),
        ];
    }

    protected function user($ticket)
    {
        return $this->hasOne($ticket, BasicUserSerializer::class);
    }

    protected function assignedTo($ticket)
    {
        return $this->hasOne($ticket, BasicUserSerializer::class);
    }

    protected function closedBy($ticket)
    {
        return $this->hasOne($ticket, BasicUserSerializer::class);
    }

    protected function replies($ticket)
    {
        return $this->hasMany($ticket, TicketReplySerializer::class);
    }
}
