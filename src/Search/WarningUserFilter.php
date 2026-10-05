<?php

namespace Prm\Moderation\Search;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\User\Exception\PermissionDeniedException;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class WarningUserFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'user';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $userId = $this->asInt($value);
        $actor = $state->getActor();

        if (! $userId) {
            throw new PermissionDeniedException();
        }

        if ((int) $actor->id !== $userId) {
            $actor->assertPermission($actor->hasPermission('moderation.access'));
        }

        $state->getQuery()->where('user_id', $userId);
    }
}
