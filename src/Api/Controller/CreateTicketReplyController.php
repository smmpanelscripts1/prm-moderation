<?php

namespace Prm\Moderation\Api\Controller;

use Carbon\Carbon;
use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Flarum\Notification\NotificationSyncer;
use Illuminate\Support\Arr;
use Prm\Moderation\Api\Serializer\TicketReplySerializer;
use Prm\Moderation\Notification\TicketRepliedBlueprint;
use Prm\Moderation\Ticket;
use Prm\Moderation\TicketReply;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateTicketReplyController extends AbstractCreateController
{
    public $serializer = TicketReplySerializer::class;

    public $include = ['user', 'ticket', 'ticket.user', 'ticket.assignedTo'];

    /**
     * @var NotificationSyncer
     */
    protected $notifications;

    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $attributes = Arr::get($request->getParsedBody(), 'data.attributes', []);
        $relationships = Arr::get($request->getParsedBody(), 'data.relationships', []);

        $ticketId = Arr::get($relationships, 'ticket.data.id');
        $ticket = Ticket::query()->findOrFail($ticketId);

        $actor->assertCan('reply', $ticket);

        $isStaff = $actor->hasPermission('moderation.access');

        if ($ticket->isClosed() && ! $isStaff) {
            throw new ValidationException(['content' => 'This ticket is closed.']);
        }

        $content = trim((string) Arr::get($attributes, 'content', ''));
        if ($content === '' || mb_strlen($content) > 5000) {
            throw new ValidationException(['content' => 'A reply is required.']);
        }

        $reply = new TicketReply();
        $reply->ticket_id = $ticket->id;
        $reply->user_id = $actor->id;
        $reply->content = $content;
        $reply->is_staff = $isStaff;
        $reply->save();

        $ticket->last_replied_at = Carbon::now();

        if ($isStaff) {
            if (! $ticket->assigned_to_id) {
                $ticket->assigned_to_id = $actor->id;
            }
            $ticket->status = Ticket::STATUS_ANSWERED;
            $ticket->closed_at = null;
            $ticket->closed_by_id = null;
        } else {
            $ticket->status = Ticket::STATUS_WAITING;
        }

        $ticket->save();

        $reply->load(['user', 'ticket.user', 'ticket.assignedTo']);

        $recipients = [];
        if ($isStaff) {
            if ((int) $ticket->user_id !== (int) $actor->id) {
                $recipients[] = $ticket->user;
            }
        } elseif ($ticket->assignedTo && (int) $ticket->assigned_to_id !== (int) $actor->id) {
            $recipients[] = $ticket->assignedTo;
        }

        if ($recipients) {
            $this->notifications->sync(new TicketRepliedBlueprint($reply), $recipients);
        }

        return $reply;
    }
}
