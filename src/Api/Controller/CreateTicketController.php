<?php

namespace Prm\Moderation\Api\Controller;

use Carbon\Carbon;
use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Prm\Moderation\Api\Serializer\TicketSerializer;
use Prm\Moderation\Ticket;
use Prm\Moderation\TicketReply;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateTicketController extends AbstractCreateController
{
    public $serializer = TicketSerializer::class;

    public $include = ['user', 'assignedTo', 'replies', 'replies.user'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('create', Ticket::class);

        $attributes = Arr::get($request->getParsedBody(), 'data.attributes', []);

        $subject = trim((string) Arr::get($attributes, 'subject', ''));
        $content = trim((string) Arr::get($attributes, 'content', ''));
        $category = (string) Arr::get($attributes, 'category', '');
        $priority = (string) Arr::get($attributes, 'priority', Ticket::PRIORITIES[0]);

        if ($subject === '' || mb_strlen($subject) > 160) {
            throw new ValidationException(['subject' => 'A short subject is required.']);
        }
        if ($content === '' || mb_strlen($content) > 5000) {
            throw new ValidationException(['content' => 'Please describe your request.']);
        }
        if (! in_array($category, Ticket::CATEGORIES, true)) {
            throw new ValidationException(['category' => 'Invalid category.']);
        }
        if (! in_array($priority, Ticket::PRIORITIES, true)) {
            $priority = 'normal';
        }

        $openCount = Ticket::query()
            ->where('user_id', $actor->id)
            ->where('status', '!=', Ticket::STATUS_CLOSED)
            ->count();

        if ($openCount >= Ticket::MAX_OPEN_PER_USER) {
            throw new ValidationException(['subject' => 'You already have too many open tickets.']);
        }

        $now = Carbon::now();

        $ticket = new Ticket();
        $ticket->user_id = $actor->id;
        $ticket->subject = $subject;
        $ticket->category = $category;
        $ticket->priority = $priority;
        $ticket->status = Ticket::STATUS_OPEN;
        $ticket->last_replied_at = $now;
        $ticket->save();

        $reply = new TicketReply();
        $reply->ticket_id = $ticket->id;
        $reply->user_id = $actor->id;
        $reply->content = $content;
        $reply->is_staff = false;
        $reply->save();

        $ticket->load(['user', 'assignedTo', 'replies.user']);

        return $ticket;
    }
}
