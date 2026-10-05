<?php

namespace Prm\Moderation\Api\Resource;

use Flarum\Api\Context as FlarumContext;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Prm\Moderation\ModerationActions;
use Prm\Moderation\Warning;
use Tobyz\JsonApiServer\Context;

/**
 * @extends AbstractDatabaseResource<Warning>
 */
class WarningResource extends AbstractDatabaseResource
{
    public function __construct(
        protected ModerationActions $actions
    ) {
    }

    public function type(): string
    {
        return 'moderation-warnings';
    }

    public function model(): string
    {
        return Warning::class;
    }

    public function scope(Builder $query, Context $context): void
    {
        // Visibility enforced via WarningSearcher / WarningUserFilter.
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->authenticated()
                ->defaultInclude(['user', 'actor', 'discussion'])
                ->defaultSort('-createdAt')
                ->paginate(20, 50),
            Endpoint\Create::make()
                ->authenticated()
                ->visible(fn (FlarumContext $context) => $context->getActor()->can('create', Warning::class))
                ->defaultInclude(['user', 'actor', 'discussion']),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Integer::make('points')
                ->requiredOnCreate()
                ->writableOnCreate()
                ->min(1)
                ->max(100),
            Schema\Str::make('reason')
                ->requiredOnCreate()
                ->writableOnCreate()
                ->minLength(1)
                ->maxLength(255),
            Schema\Str::make('comment')
                ->writableOnCreate()
                ->nullable()
                ->maxLength(5000)
                ->set(function (Warning $warning, ?string $value) {
                    $value = is_string($value) ? trim($value) : '';
                    $warning->comment = $value !== '' ? $value : null;
                }),
            Schema\DateTime::make('createdAt'),

            Schema\Relationship\ToOne::make('user')
                ->type('users')
                ->includable()
                ->writable(fn (Warning $warning, FlarumContext $context) => $context->creating())
                ->requiredOnCreate()
                ->set(function (Warning $warning, User $user) {
                    $warning->user_id = $user->id;
                }),
            Schema\Relationship\ToOne::make('actor')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('post')
                ->type('posts')
                ->includable()
                ->writable(fn (Warning $warning, FlarumContext $context) => $context->creating())
                ->set(function (Warning $warning, Post $post) {
                    $warning->post_id = $post->id;
                    $warning->discussion_id = $post->discussion_id;
                }),
            Schema\Relationship\ToOne::make('discussion')
                ->type('discussions')
                ->includable()
                ->writable(fn (Warning $warning, FlarumContext $context) => $context->creating())
                ->set(function (Warning $warning, Discussion $discussion) {
                    $warning->discussion_id = $discussion->id;
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
        $target = User::query()->findOrFail($model->user_id);
        $post = $model->post_id ? Post::query()->find($model->post_id) : null;
        $discussion = $model->discussion_id
            ? Discussion::query()->find($model->discussion_id)
            : ($post ? $post->discussion : null);

        return $this->actions->warn(
            $actor,
            $target,
            (int) $model->points,
            (string) $model->reason,
            $model->comment,
            $post,
            $discussion
        );
    }
}
