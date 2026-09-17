<?php

namespace Prm\Moderation\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;
use InvalidArgumentException;
use Prm\Moderation\TicketReply;

class TicketReplySerializer extends AbstractSerializer
{
    protected $type = 'moderation-ticket-replies';

    protected function getDefaultAttributes($reply)
    {
        if (! ($reply instanceof TicketReply)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.TicketReply::class
            );
        }

        return [
            'content' => $reply->content,
            'isStaff' => (bool) $reply->is_staff,
            'createdAt' => $this->formatDate($reply->created_at),
        ];
    }

    protected function user($reply)
    {
        return $this->hasOne($reply, BasicUserSerializer::class);
    }

    protected function ticket($reply)
    {
        return $this->hasOne($reply, TicketSerializer::class);
    }
}
