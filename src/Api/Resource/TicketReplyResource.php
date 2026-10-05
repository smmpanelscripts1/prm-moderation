<?php

namespace Prm\Moderation\Api\Resource;

use Carbon\Carbon;
use Flarum\Api\Context as FlarumContext;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Foundation\ValidationException;
use Flarum\Notification\NotificationSyncer;
use Illuminate\Database\Eloquent\Builder;
use Prm\Moderation\Notification\TicketRepliedBlueprint;
use Prm\Moderation\Ticket;
use Prm\Moderation\TicketReply;
use Tobyz\JsonApiServer\Context;

/**
 * @extends AbstractDatabaseResource<TicketReply>
 */
class TicketReplyResource extends AbstractDatabaseResource
{
    public function __construct(
        protected NotificationSyncer $notifications
    ) {
    }

    public function type(): string
    {
        return 'moderation-ticket-replies';
    }

    public function model(): string
    {
        return TicketReply::class;
    }

    public function scope(Builder $query, Context $context): void
    {
        // Replies are accessed via ticket includes / create endpoint.
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make()
                ->authenticated()
                ->defaultInclude(['user', 'ticket', 'ticket.user', 'ticket.assignedTo']),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('content')
                ->requiredOnCreate()
                ->writableOnCreate()
                ->minLength(1)
                ->maxLength(5000)
                ->set(function (TicketReply $reply, string $value) {
                    $reply->content = trim($value);
                }),
            Schema\Boolean::make('isStaff'),
            Schema\DateTime::make('createdAt'),

            Schema\Relationship\ToOne::make('user')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('ticket')
                ->type('moderation-tickets')
                ->includable()
                ->writable(fn (TicketReply $reply, FlarumContext $context) => $context->creating())
                ->requiredOnCreate()
                ->set(function (TicketReply $reply, Ticket $ticket, FlarumContext $context) {
                    $actor = $context->getActor();
                    $actor->assertCan('reply', $ticket);

                    $reply->ticket_id = $ticket->id;
                }),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt'),
        ];
    }

    public function creating(object $model, Context $context): ?object
    {
        $actor = $context->getActor();
        $ticket = Ticket::query()->findOrFail($model->ticket_id);
        $isStaff = $actor->hasPermission('moderation.access');

        if ($ticket->isClosed() && ! $isStaff) {
            throw new ValidationException(['content' => 'This ticket is closed.']);
        }

        if ($model->content === '' || mb_strlen($model->content) > 5000) {
            throw new ValidationException(['content' => 'A reply is required.']);
        }

        $model->user_id = $actor->id;
        $model->is_staff = $isStaff;

        return $model;
    }

    public function created(object $model, Context $context): ?object
    {
        $actor = $context->getActor();
        $ticket = Ticket::query()->findOrFail($model->ticket_id);
        $isStaff = (bool) $model->is_staff;

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

        $model->load(['user', 'ticket.user', 'ticket.assignedTo']);

        $recipients = [];
        if ($isStaff) {
            if ((int) $ticket->user_id !== (int) $actor->id) {
                $recipients[] = $ticket->user;
            }
        } elseif ($ticket->assignedTo && (int) $ticket->assigned_to_id !== (int) $actor->id) {
            $recipients[] = $ticket->assignedTo;
        }

        if ($recipients) {
            $this->notifications->sync(new TicketRepliedBlueprint($model), $recipients);
        }

        return $model;
    }
}
