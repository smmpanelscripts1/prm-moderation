<?php

namespace Prm\Moderation\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\User\User;
use Prm\Moderation\Report;

class UserResourceFields
{
    public function __invoke(): array
    {
        return [
            Schema\Integer::make('warningPoints')
                ->visible(function (User $user, Context $context) {
                    $actor = $context->getActor();

                    return ! $actor->isGuest() && (
                        (int) $actor->id === (int) $user->id
                        || $actor->hasPermission('moderation.access')
                    );
                })
                ->get(fn (User $user) => (int) $user->warning_points),
            Schema\Integer::make('warningCount')
                ->visible(function (User $user, Context $context) {
                    $actor = $context->getActor();

                    return ! $actor->isGuest() && (
                        (int) $actor->id === (int) $user->id
                        || $actor->hasPermission('moderation.access')
                    );
                })
                ->get(fn (User $user) => (int) $user->warning_count),
            Schema\Boolean::make('canWarn')
                ->get(function (User $user, Context $context) {
                    $actor = $context->getActor();

                    return $actor->hasPermission('moderation.access')
                        && (int) $actor->id !== (int) $user->id
                        && (! $user->isAdmin() || $actor->isAdmin());
                }),
            Schema\Boolean::make('canMarkSpammer')
                ->get(function (User $user, Context $context) {
                    $actor = $context->getActor();

                    return $actor->hasPermission('moderation.access')
                        && (int) $actor->id !== (int) $user->id
                        && (! $user->isAdmin() || $actor->isAdmin());
                }),
            Schema\Boolean::make('canReportUser')
                ->get(function (User $user, Context $context) {
                    $actor = $context->getActor();

                    return ! $actor->isGuest()
                        && (int) $actor->id !== (int) $user->id
                        && $actor->can('create', Report::class);
                }),
        ];
    }
}
