<?php

namespace Prm\Moderation;

use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Extend;
use Flarum\User\User;
use Prm\Moderation\Access\ReportPolicy;
use Prm\Moderation\Access\TicketPolicy;
use Prm\Moderation\Access\WarningPolicy;
use Prm\Moderation\Api\Controller\CreateReportController;
use Prm\Moderation\Api\Controller\CreateTicketController;
use Prm\Moderation\Api\Controller\CreateTicketReplyController;
use Prm\Moderation\Api\Controller\CreateWarningController;
use Prm\Moderation\Api\Controller\HandleReportController;
use Prm\Moderation\Api\Controller\ListReportsController;
use Prm\Moderation\Api\Controller\ListTicketsController;
use Prm\Moderation\Api\Controller\ListWarningsController;
use Prm\Moderation\Api\Controller\ShowStatsController;
use Prm\Moderation\Api\Controller\ShowTicketController;
use Prm\Moderation\Api\Controller\UpdateTicketController;
use Prm\Moderation\Forum\UserWarningsContent;
use Prm\Moderation\Notification\TicketRepliedBlueprint;
use Prm\Moderation\Notification\WarningReceivedBlueprint;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->route('/moderation', 'moderation')
        ->route('/tickets', 'tickets')
        ->route('/tickets/{id}', 'tickets.show')
        ->route('/u/{username}/warnings', 'user.warnings', UserWarningsContent::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Routes('api'))
        ->get('/moderation-reports', 'moderation-reports.index', ListReportsController::class)
        ->post('/moderation-reports', 'moderation-reports.create', CreateReportController::class)
        ->patch('/moderation-reports/{id}', 'moderation-reports.handle', HandleReportController::class)
        ->get('/moderation-warnings', 'moderation-warnings.index', ListWarningsController::class)
        ->post('/moderation-warnings', 'moderation-warnings.create', CreateWarningController::class)
        ->get('/moderation-stats', 'moderation-stats.show', ShowStatsController::class)
        ->get('/moderation-tickets', 'moderation-tickets.index', ListTicketsController::class)
        ->post('/moderation-tickets', 'moderation-tickets.create', CreateTicketController::class)
        ->get('/moderation-tickets/{id}', 'moderation-tickets.show', ShowTicketController::class)
        ->patch('/moderation-tickets/{id}', 'moderation-tickets.update', UpdateTicketController::class)
        ->post('/moderation-ticket-replies', 'moderation-ticket-replies.create', CreateTicketReplyController::class),

    (new Extend\Model(User::class))
        ->hasMany('moderationWarnings', Warning::class, 'user_id')
        ->hasMany('moderationReports', Report::class, 'target_user_id')
        ->hasMany('moderationTickets', Ticket::class, 'user_id')
        ->cast('warning_points', 'int')
        ->cast('warning_count', 'int'),

    (new Extend\ApiSerializer(BasicUserSerializer::class))
        ->attributes(function (BasicUserSerializer $serializer, User $user) {
            $actor = $serializer->getActor();
            $canSee = ! $actor->isGuest() && (
                (int) $actor->id === (int) $user->id || $actor->hasPermission('moderation.access')
            );

            return $canSee ? [
                'warningPoints' => (int) $user->warning_points,
                'warningCount' => (int) $user->warning_count,
            ] : [];
        }),

    (new Extend\ApiSerializer(UserSerializer::class))
        ->attributes(function (UserSerializer $serializer, User $user) {
            $actor = $serializer->getActor();

            return [
                'canWarn' => $actor->hasPermission('moderation.access')
                    && (int) $actor->id !== (int) $user->id
                    && (! $user->isAdmin() || $actor->isAdmin()),
                'canReportUser' => ! $actor->isGuest()
                    && (int) $actor->id !== (int) $user->id
                    && $actor->can('create', Report::class),
            ];
        }),

    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attributes(function (ForumSerializer $serializer) {
            $actor = $serializer->getActor();
            $canAccess = $actor->hasPermission('moderation.access');

            $openTickets = $canAccess
                ? Ticket::query()->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_WAITING])->count()
                : 0;

            $waitingForUser = ! $actor->isGuest()
                ? Ticket::query()->where('user_id', $actor->id)->where('status', Ticket::STATUS_ANSWERED)->count()
                : 0;

            return [
                'canAccessModeration' => $canAccess,
                'canReport' => $actor->can('create', Report::class),
                'canCreateTicket' => ! $actor->isGuest() && $actor->can('create', Ticket::class),
                'pendingModerationReports' => $canAccess
                    ? Report::query()->where('status', Report::STATUS_PENDING)->count()
                    : 0,
                'openModerationTickets' => $openTickets,
                'waitingSupportTickets' => $waitingForUser,
            ];
        }),

    (new Extend\Policy())
        ->modelPolicy(Report::class, ReportPolicy::class)
        ->modelPolicy(Warning::class, WarningPolicy::class)
        ->modelPolicy(Ticket::class, TicketPolicy::class),

    (new Extend\Notification())
        ->type(WarningReceivedBlueprint::class, BasicUserSerializer::class, ['alert'])
        ->type(TicketRepliedBlueprint::class, \Prm\Moderation\Api\Serializer\TicketSerializer::class, ['alert']),
];
