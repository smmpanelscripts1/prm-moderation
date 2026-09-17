<?php

namespace Prm\Moderation\Notification;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;
use Prm\Moderation\Ticket;
use Prm\Moderation\TicketReply;

class TicketRepliedBlueprint implements BlueprintInterface
{
    /**
     * @var TicketReply
     */
    public $reply;

    public function __construct(TicketReply $reply)
    {
        $this->reply = $reply;
    }

    public function getFromUser()
    {
        return $this->reply->user;
    }

    public function getSubject()
    {
        return $this->reply->ticket;
    }

    public function getData()
    {
        return [
            'ticketId' => $this->reply->ticket_id,
            'replyId' => $this->reply->id,
            'isStaff' => (bool) $this->reply->is_staff,
        ];
    }

    public static function getType()
    {
        return 'moderationTicketReplied';
    }

    public static function getSubjectModel()
    {
        return Ticket::class;
    }
}
