<?php

namespace Prm\Moderation\Search;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Prm\Moderation\Report;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class ReportStatusFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'status';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $status = $this->asString($value);

        if (! in_array($status, [Report::STATUS_PENDING, Report::STATUS_RESOLVED, Report::STATUS_REJECTED], true)) {
            $status = Report::STATUS_PENDING;
        }

        $state->getQuery()->where('status', $status);
    }
}
