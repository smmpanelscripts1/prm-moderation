<?php

namespace Prm\Moderation\Api\Resource;

use Carbon\Carbon;
use Flarum\Api\Context as FlarumContext;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\ValidationException;
use Flarum\Post\Post;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Prm\Moderation\ModerationActions;
use Prm\Moderation\Report;
use Tobyz\JsonApiServer\Context;

/**
 * @extends AbstractDatabaseResource<Report>
 */
class ReportResource extends AbstractDatabaseResource
{
    protected bool $pendingDeleteContent = false;
    protected bool $pendingWarn = false;
    protected bool $pendingBan = false;
    protected int $pendingWarningPoints = 1;
    protected string $pendingWarningReason = '';
    protected ?string $pendingWarningComment = null;
    protected int $pendingBanDays = 0;
    protected ?string $pendingBanReason = null;
    protected ?string $pendingStatus = null;

    public function __construct(
        protected ModerationActions $actions
    ) {
    }

    public function type(): string
    {
        return 'moderation-reports';
    }

    public function model(): string
    {
        return Report::class;
    }

    public function scope(Builder $query, Context $context): void
    {
        // Visibility enforced via ReportSearcher / create policy.
    }

    public function newModel(Context $context): object
    {
        if ($context->creating(self::class)) {
            return new Report([
                'status' => Report::STATUS_PENDING,
            ]);
        }

        return parent::newModel($context);
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->authenticated()
                ->defaultInclude(['reporter', 'targetUser', 'handledBy', 'post', 'discussion'])
                ->defaultSort('-createdAt')
                ->paginate(20, 50),
            Endpoint\Create::make()
                ->authenticated()
                ->visible(fn (FlarumContext $context) => $context->getActor()->can('create', Report::class))
                ->defaultInclude(['reporter', 'targetUser', 'post', 'discussion']),
            Endpoint\Update::make()
                ->authenticated()
                ->can('handle')
                ->defaultInclude(['reporter', 'targetUser', 'handledBy', 'post', 'discussion']),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('targetType')
                ->requiredOnCreate()
                ->writableOnCreate()
                ->in([Report::TYPE_USER, Report::TYPE_POST, Report::TYPE_DISCUSSION]),
            Schema\Str::make('reason')
                ->requiredOnCreate()
                ->writableOnCreate()
                ->in(['spam', 'abuse', 'illegal', 'other']),
            Schema\Str::make('reasonDetail')
                ->writableOnCreate()
                ->nullable()
                ->maxLength(2000)
                ->set(function (Report $report, ?string $value) {
                    $value = is_string($value) ? trim($value) : '';
                    $report->reason_detail = $value !== '' ? $value : null;
                }),
            Schema\Str::make('status')
                ->writableOnUpdate()
                ->set(function (Report $report, string $value) {
                    $this->pendingStatus = $value;
                }),
            Schema\DateTime::make('createdAt'),
            Schema\DateTime::make('handledAt'),

            Schema\Boolean::make('deleteContent')
                ->writableOnUpdate()
                ->visible(false)
                ->set(function (Report $report, bool $value) {
                    $this->pendingDeleteContent = $value;
                }),
            Schema\Boolean::make('warn')
                ->writableOnUpdate()
                ->visible(false)
                ->set(function (Report $report, bool $value) {
                    $this->pendingWarn = $value;
                }),
            Schema\Boolean::make('ban')
                ->writableOnUpdate()
                ->visible(false)
                ->set(function (Report $report, bool $value) {
                    $this->pendingBan = $value;
                }),
            Schema\Integer::make('warningPoints')
                ->writableOnUpdate()
                ->visible(false)
                ->set(function (Report $report, int $value) {
                    $this->pendingWarningPoints = $value;
                }),
            Schema\Str::make('warningReason')
                ->writableOnUpdate()
                ->visible(false)
                ->set(function (Report $report, ?string $value) {
                    $this->pendingWarningReason = (string) $value;
                }),
            Schema\Str::make('warningComment')
                ->writableOnUpdate()
                ->visible(false)
                ->nullable()
                ->set(function (Report $report, ?string $value) {
                    $this->pendingWarningComment = $value;
                }),
            Schema\Integer::make('banDays')
                ->writableOnUpdate()
                ->visible(false)
                ->set(function (Report $report, int $value) {
                    $this->pendingBanDays = $value;
                }),
            Schema\Str::make('banReason')
                ->writableOnUpdate()
                ->visible(false)
                ->nullable()
                ->set(function (Report $report, ?string $value) {
                    $this->pendingBanReason = $value;
                }),

            Schema\Relationship\ToOne::make('reporter')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('targetUser')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('handledBy')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('post')
                ->type('posts')
                ->includable()
                ->writable(fn (Report $report, FlarumContext $context) => $context->creating())
                ->set(function (Report $report, Post $post) {
                    $report->post_id = $post->id;
                    $report->discussion_id = $post->discussion_id;
                    $report->target_user_id = $post->user_id;
                    $report->target_type = Report::TYPE_POST;
                }),
            Schema\Relationship\ToOne::make('discussion')
                ->type('discussions')
                ->includable()
                ->writable(fn (Report $report, FlarumContext $context) => $context->creating())
                ->set(function (Report $report, Discussion $discussion) {
                    $report->discussion_id = $discussion->id;
                    $report->target_user_id = $discussion->user_id;
                    $report->target_type = Report::TYPE_DISCUSSION;
                }),
            Schema\Relationship\ToOne::make('user')
                ->type('users')
                ->includable(false)
                ->writable(fn (Report $report, FlarumContext $context) => $context->creating())
                ->set(function (Report $report, User $user) {
                    $report->target_user_id = $user->id;
                    $report->target_type = Report::TYPE_USER;
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
        $model->reporter_id = $actor->id;
        $model->status = Report::STATUS_PENDING;

        if (! $model->target_user_id) {
            throw new ValidationException(['target' => 'Could not determine the reported user.']);
        }

        if ((int) $model->target_user_id === (int) $actor->id) {
            throw new PermissionDeniedException();
        }

        if ($model->reason === 'other' && ! $model->reason_detail) {
            throw new ValidationException(['reasonDetail' => 'Please explain this report.']);
        }

        $existing = Report::query()->firstOrNew([
            'reporter_id' => $actor->id,
            'target_type' => $model->target_type,
            'target_user_id' => $model->target_user_id,
            'post_id' => $model->post_id,
            'discussion_id' => $model->discussion_id,
            'status' => Report::STATUS_PENDING,
        ]);

        $existing->reason = $model->reason;
        $existing->reason_detail = $model->reason_detail;

        return $existing;
    }

    public function updating(object $model, Context $context): ?object
    {
        $actor = $context->getActor();
        $status = $this->pendingStatus ?: Arr::get($context->body(), 'data.attributes.status');

        if (! in_array($status, [Report::STATUS_RESOLVED, Report::STATUS_REJECTED], true)) {
            throw new ValidationException(['status' => 'Invalid status.']);
        }

        $model->loadMissing(['targetUser', 'post', 'discussion']);
        $target = $model->targetUser;

        if ($status === Report::STATUS_RESOLVED && $target) {
            if ($this->pendingDeleteContent) {
                $this->actions->hideContent($actor, $model->post, $model->discussion);
            }

            if ($this->pendingWarn) {
                $this->actions->warn(
                    $actor,
                    $target,
                    $this->pendingWarningPoints,
                    $this->pendingWarningReason,
                    $this->pendingWarningComment,
                    $model->post,
                    $model->discussion,
                    $model
                );
            }

            if ($this->pendingBan) {
                $this->actions->ban(
                    $actor,
                    $target,
                    $this->pendingBanDays,
                    $this->pendingBanReason
                );
            }
        }

        $model->status = $status;
        $model->handled_by_id = $actor->id;
        $model->handled_at = Carbon::now();

        return $model;
    }
}
