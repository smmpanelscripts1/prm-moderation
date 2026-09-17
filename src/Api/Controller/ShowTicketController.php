<?php

namespace Prm\Moderation\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Prm\Moderation\Api\Serializer\TicketSerializer;
use Prm\Moderation\Ticket;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowTicketController extends AbstractShowController
{
    public $serializer = TicketSerializer::class;

    public $include = ['user', 'assignedTo', 'closedBy', 'replies', 'replies.user'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');
        $ticket = Ticket::query()->findOrFail($id);

        $actor->assertCan('view', $ticket);

        $ticket->load(['user', 'assignedTo', 'closedBy', 'replies.user']);

        return $ticket;
    }
}
