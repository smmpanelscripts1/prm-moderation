<?php

namespace Prm\Moderation\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Prm\Moderation\Report;
use Prm\Moderation\Ticket;

class ForumResourceFields
{
    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('canAccessModeration')
                ->get(fn (object $model, Context $context) => $context->getActor()->hasPermission('moderation.access')),
            Schema\Boolean::make('canReport')
                ->get(fn (object $model, Context $context) => $context->getActor()->can('create', Report::class)),
            Schema\Boolean::make('canCreateTicket')
                ->get(function (object $model, Context $context) {
                    $actor = $context->getActor();

                    return ! $actor->isGuest() && $actor->can('create', Ticket::class);
                }),
            Schema\Integer::make('pendingModerationReports')
                ->get(function (object $model, Context $context) {
                    $actor = $context->getActor();

                    if (! $actor->hasPermission('moderation.access')) {
                        return 0;
                    }

                    return Report::query()->where('status', Report::STATUS_PENDING)->count();
                }),
            Schema\Integer::make('openModerationTickets')
                ->get(function (object $model, Context $context) {
                    $actor = $context->getActor();

                    if (! $actor->hasPermission('moderation.access')) {
                        return 0;
                    }

                    return Ticket::query()
                        ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_WAITING])
                        ->count();
                }),
            Schema\Integer::make('waitingSupportTickets')
                ->get(function (object $model, Context $context) {
                    $actor = $context->getActor();

                    if ($actor->isGuest()) {
                        return 0;
                    }

                    return Ticket::query()
                        ->where('user_id', $actor->id)
                        ->where('status', Ticket::STATUS_ANSWERED)
                        ->count();
                }),
        ];
    }
}
