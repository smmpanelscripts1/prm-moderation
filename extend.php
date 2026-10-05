<?php

namespace Prm\Moderation;

use Flarum\Api\Resource as CoreResource;
use Flarum\Extend;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\User\User;
use Prm\Moderation\Access\ReportPolicy;
use Prm\Moderation\Access\TicketPolicy;
use Prm\Moderation\Access\WarningPolicy;
use Prm\Moderation\Api\Controller\ShowStatsController;
use Prm\Moderation\Api\ForumResourceFields;
use Prm\Moderation\Api\Resource\ReportResource;
use Prm\Moderation\Api\Resource\TicketReplyResource;
use Prm\Moderation\Api\Resource\TicketResource;
use Prm\Moderation\Api\Resource\WarningResource;
use Prm\Moderation\Api\UserResourceFields;
use Prm\Moderation\Forum\UserWarningsContent;
use Prm\Moderation\Notification\TicketRepliedBlueprint;
use Prm\Moderation\Notification\WarningReceivedBlueprint;
use Prm\Moderation\Search\ReportSearcher;
use Prm\Moderation\Search\ReportStatusFilter;
use Prm\Moderation\Search\TicketSearcher;
use Prm\Moderation\Search\TicketStatusFilter;
use Prm\Moderation\Search\WarningSearcher;
use Prm\Moderation\Search\WarningUserFilter;

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
        ->get('/moderation-stats', 'moderation-stats.show', ShowStatsController::class),

    (new Extend\Model(User::class))
        ->hasMany('moderationWarnings', Warning::class, 'user_id')
        ->hasMany('moderationReports', Report::class, 'target_user_id')
        ->hasMany('moderationTickets', Ticket::class, 'user_id')
        ->cast('warning_points', 'int')
        ->cast('warning_count', 'int'),

    new Extend\ApiResource(ReportResource::class),
    new Extend\ApiResource(WarningResource::class),
    new Extend\ApiResource(TicketResource::class),
    new Extend\ApiResource(TicketReplyResource::class),

    (new Extend\ApiResource(CoreResource\UserResource::class))
        ->fields(UserResourceFields::class),

    (new Extend\ApiResource(CoreResource\ForumResource::class))
        ->fields(ForumResourceFields::class),

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addSearcher(Report::class, ReportSearcher::class)
        ->addFilter(ReportSearcher::class, ReportStatusFilter::class)
        ->addSearcher(Warning::class, WarningSearcher::class)
        ->addFilter(WarningSearcher::class, WarningUserFilter::class)
        ->addSearcher(Ticket::class, TicketSearcher::class)
        ->addFilter(TicketSearcher::class, TicketStatusFilter::class),

    (new Extend\Policy())
        ->modelPolicy(Report::class, ReportPolicy::class)
        ->modelPolicy(Warning::class, WarningPolicy::class)
        ->modelPolicy(Ticket::class, TicketPolicy::class),

    (new Extend\Notification())
        ->type(WarningReceivedBlueprint::class, ['alert'])
        ->type(TicketRepliedBlueprint::class, ['alert']),
];
