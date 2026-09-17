<?php

namespace Prm\Moderation\Api\Controller;

use Carbon\Carbon;
use Flarum\Api\Controller\AbstractShowController;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Prm\Moderation\Api\Serializer\TicketSerializer;
use Prm\Moderation\Ticket;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class UpdateTicketController extends AbstractShowController
{
    public $serializer = TicketSerializer::class;

    public $include = ['user', 'assignedTo', 'closedBy', 'replies', 'replies.user'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');
        $ticket = Ticket::query()->findOrFail($id);
        $attributes = Arr::get($request->getParsedBody(), 'data.attributes', []);

        if (array_key_exists('status', $attributes)) {
            $status = (string) $attributes['status'];

            if ($status === Ticket::STATUS_CLOSED) {
                $actor->assertCan('close', $ticket);
                $ticket->status = Ticket::STATUS_CLOSED;
                $ticket->closed_at = Carbon::now();
                $ticket->closed_by_id = $actor->id;
            } elseif ($status === Ticket::STATUS_OPEN) {
                $actor->assertCan('reopen', $ticket);
                $ticket->status = Ticket::STATUS_WAITING;
                $ticket->closed_at = null;
                $ticket->closed_by_id = null;
            } else {
                throw new ValidationException(['status' => 'Invalid status.']);
            }

            $ticket->save();
        }

        $ticket->load(['user', 'assignedTo', 'closedBy', 'replies.user']);

        return $ticket;
    }
}
