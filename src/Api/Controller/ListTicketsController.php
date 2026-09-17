<?php

namespace Prm\Moderation\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Prm\Moderation\Api\Serializer\TicketSerializer;
use Prm\Moderation\Ticket;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListTicketsController extends AbstractListController
{
    public $serializer = TicketSerializer::class;

    public $include = ['user', 'assignedTo'];

    public $limit = 20;

    public $maxLimit = 50;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $filters = $this->extractFilter($request);
        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $include = $this->extractInclude($request);
        $status = Arr::get($filters, 'status');

        $query = Ticket::query()->orderByDesc('last_replied_at')->orderByDesc('id');

        if ($actor->hasPermission('moderation.access')) {
            if ($status === 'inbox') {
                $query->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_WAITING]);
            } elseif (in_array($status, [Ticket::STATUS_OPEN, Ticket::STATUS_WAITING, Ticket::STATUS_ANSWERED, Ticket::STATUS_CLOSED], true)) {
                $query->where('status', $status);
            }
        } else {
            $actor->assertCan('create', Ticket::class);
            $query->where('user_id', $actor->id);

            if ($status === 'open') {
                $query->where('status', '!=', Ticket::STATUS_CLOSED);
            } elseif ($status === Ticket::STATUS_CLOSED) {
                $query->where('status', Ticket::STATUS_CLOSED);
            }
        }

        $total = (clone $query)->count();
        $results = $query->skip($offset)->take($limit)->get();

        $document->setMeta(['total' => $total]);

        $this->loadRelations($results, $include);

        return $results;
    }
}
