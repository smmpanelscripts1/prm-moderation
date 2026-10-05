<?php

namespace Prm\Moderation\Notification;

use Flarum\Database\AbstractModel;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;
use Prm\Moderation\Ticket;
use Prm\Moderation\TicketReply;

class TicketRepliedBlueprint implements BlueprintInterface, AlertableInterface
{
    public function __construct(
        public TicketReply $reply
    ) {
    }

    public function getFromUser(): ?User
    {
        return $this->reply->user;
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->reply->ticket;
    }

    public function getData(): mixed
    {
        return [
            'ticketId' => $this->reply->ticket_id,
            'replyId' => $this->reply->id,
            'isStaff' => (bool) $this->reply->is_staff,
        ];
    }

    public static function getType(): string
    {
        return 'moderationTicketReplied';
    }

    public static function getSubjectModel(): string
    {
        return Ticket::class;
    }
}
