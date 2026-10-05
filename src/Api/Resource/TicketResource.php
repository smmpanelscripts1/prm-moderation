<?php

namespace Prm\Moderation\Api\Resource;

use Carbon\Carbon;
use Flarum\Api\Context as FlarumContext;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Foundation\ValidationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Prm\Moderation\Ticket;
use Prm\Moderation\TicketReply;
use Tobyz\JsonApiServer\Context;

/**
 * @extends AbstractDatabaseResource<Ticket>
 */
class TicketResource extends AbstractDatabaseResource
{
    protected ?string $pendingContent = null;
    protected ?string $pendingStatus = null;

    public function type(): string
    {
        return 'moderation-tickets';
    }

    public function model(): string
    {
        return Ticket::class;
    }

    public function scope(Builder $query, Context $context): void
    {
        // Visibility enforced via TicketSearcher / TicketStatusFilter / policies.
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->authenticated()
                ->defaultInclude(['user', 'assignedTo'])
                ->defaultSort('-lastRepliedAt')
                ->paginate(20, 50),
            Endpoint\Show::make()
                ->authenticated()
                ->can('view')
                ->defaultInclude(['user', 'assignedTo', 'closedBy', 'replies', 'replies.user']),
            Endpoint\Create::make()
                ->authenticated()
                ->visible(fn (FlarumContext $context) => $context->getActor()->can('create', Ticket::class))
                ->defaultInclude(['user', 'assignedTo', 'replies', 'replies.user']),
            Endpoint\Update::make()
                ->authenticated()
                ->can('edit')
                ->defaultInclude(['user', 'assignedTo', 'closedBy', 'replies', 'replies.user']),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('subject')
                ->requiredOnCreate()
                ->writableOnCreate()
                ->minLength(1)
                ->maxLength(160),
            Schema\Str::make('category')
                ->requiredOnCreate()
                ->writableOnCreate()
                ->in(Ticket::CATEGORIES),
            Schema\Str::make('priority')
                ->writableOnCreate()
                ->in(Ticket::PRIORITIES)
                ->default('normal'),
            Schema\Str::make('content')
                ->writableOnCreate()
                ->requiredOnCreate()
                ->visible(false)
                ->minLength(1)
                ->maxLength(5000)
                ->set(function (Ticket $ticket, string $value) {
                    $this->pendingContent = trim($value);
                }),
            Schema\Str::make('status')
                ->writableOnUpdate()
                ->set(function (Ticket $ticket, string $value) {
                    $this->pendingStatus = $value;
                }),
            Schema\DateTime::make('createdAt'),
            Schema\DateTime::make('lastRepliedAt'),
            Schema\DateTime::make('closedAt'),
            Schema\Boolean::make('canReply')
                ->get(function (Ticket $ticket, FlarumContext $context) {
                    return $context->getActor()->can('reply', $ticket) && ! $ticket->isClosed();
                }),
            Schema\Boolean::make('canClose')
                ->get(function (Ticket $ticket, FlarumContext $context) {
                    return $context->getActor()->can('close', $ticket) && ! $ticket->isClosed();
                }),
            Schema\Boolean::make('canReopen')
                ->get(function (Ticket $ticket, FlarumContext $context) {
                    return $context->getActor()->can('reopen', $ticket) && $ticket->isClosed();
                }),

            Schema\Relationship\ToOne::make('user')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('assignedTo')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('closedBy')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToMany::make('replies')
                ->type('moderation-ticket-replies')
                ->includable(),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt'),
            SortColumn::make('lastRepliedAt'),
        ];
    }

    public function creating(object $model, Context $context): ?object
    {
        $actor = $context->getActor();
        $content = $this->pendingContent ?? '';

        if ($content === '') {
            throw new ValidationException(['content' => 'Please describe your request.']);
        }

        $openCount = Ticket::query()
            ->where('user_id', $actor->id)
            ->where('status', '!=', Ticket::STATUS_CLOSED)
            ->count();

        if ($openCount >= Ticket::MAX_OPEN_PER_USER) {
            throw new ValidationException(['subject' => 'You already have too many open tickets.']);
        }

        $priority = $model->priority ?: 'normal';
        if (! in_array($priority, Ticket::PRIORITIES, true)) {
            $priority = 'normal';
        }

        $model->user_id = $actor->id;
        $model->priority = $priority;
        $model->status = Ticket::STATUS_OPEN;
        $model->last_replied_at = Carbon::now();

        return $model;
    }

    public function created(object $model, Context $context): ?object
    {
        $reply = new TicketReply();
        $reply->ticket_id = $model->id;
        $reply->user_id = $context->getActor()->id;
        $reply->content = (string) $this->pendingContent;
        $reply->is_staff = false;
        $reply->save();

        $model->load(['user', 'assignedTo', 'replies.user']);

        return $model;
    }

    public function updating(object $model, Context $context): ?object
    {
        $actor = $context->getActor();
        $status = $this->pendingStatus ?: Arr::get($context->body(), 'data.attributes.status');

        if ($status === null) {
            return $model;
        }

        if ($status === Ticket::STATUS_CLOSED) {
            $actor->assertCan('close', $model);
            $model->status = Ticket::STATUS_CLOSED;
            $model->closed_at = Carbon::now();
            $model->closed_by_id = $actor->id;
        } elseif ($status === Ticket::STATUS_OPEN) {
            $actor->assertCan('reopen', $model);
            $model->status = Ticket::STATUS_WAITING;
            $model->closed_at = null;
            $model->closed_by_id = null;
        } else {
            throw new ValidationException(['status' => 'Invalid status.']);
        }

        return $model;
    }
}
