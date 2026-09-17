<?php

namespace Prm\Moderation;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\ValidationException;
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Prm\Moderation\Notification\WarningReceivedBlueprint;

class ModerationActions
{
    /**
     * @var NotificationSyncer
     */
    protected $notifications;

    /**
     * @var Dispatcher
     */
    protected $events;

    public function __construct(NotificationSyncer $notifications, Dispatcher $events)
    {
        $this->notifications = $notifications;
        $this->events = $events;
    }

    public function assertCanModerate(User $actor, User $target = null): void
    {
        $actor->assertPermission('moderation.access');

        if ($target && (int) $target->id === (int) $actor->id) {
            throw new PermissionDeniedException();
        }

        if ($target && $target->isAdmin() && ! $actor->isAdmin()) {
            throw new PermissionDeniedException();
        }
    }

    public function warn(
        User $actor,
        User $target,
        int $points,
        string $reason,
        ?string $comment = null,
        ?Post $post = null,
        ?Discussion $discussion = null,
        ?Report $report = null
    ): Warning {
        $this->assertCanModerate($actor, $target);

        if ($points < 1 || $points > 100) {
            throw new ValidationException(['points' => 'Invalid warning points.']);
        }

        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 255) {
            throw new ValidationException(['reason' => 'Warning reason is required.']);
        }

        $comment = is_string($comment) ? trim($comment) : '';
        if (mb_strlen($comment) > 5000) {
            throw new ValidationException(['comment' => 'Comment is too long.']);
        }

        $warning = new Warning();
        $warning->user_id = $target->id;
        $warning->actor_id = $actor->id;
        $warning->points = $points;
        $warning->reason = $reason;
        $warning->comment = $comment !== '' ? $comment : null;
        $warning->post_id = $post ? $post->id : null;
        $warning->discussion_id = $discussion ? $discussion->id : ($post ? $post->discussion_id : null);
        $warning->report_id = $report ? $report->id : null;
        $warning->save();

        $target->warning_points = (int) $target->warning_points + $points;
        $target->warning_count = (int) $target->warning_count + 1;
        $target->save();

        $warning->setRelation('user', $target);
        $warning->setRelation('actor', $actor);

        $this->notifications->sync(new WarningReceivedBlueprint($warning), [$target]);

        return $warning;
    }

    public function hideContent(User $actor, ?Post $post = null, ?Discussion $discussion = null): void
    {
        $this->assertCanModerate($actor);

        if ($post) {
            $comment = $post instanceof CommentPost ? $post : CommentPost::query()->find($post->id);

            if ($comment) {
                $comment->hide($actor);
                $comment->save();
            }

            $discussion = $discussion ?: ($post->discussion ?: null);
            if ($discussion && (int) $discussion->first_post_id === (int) $post->id) {
                $discussion->hide($actor);
                $discussion->save();
            }

            return;
        }

        if ($discussion) {
            $discussion->hide($actor);
            $discussion->save();
        }
    }

    public function ban(User $actor, User $target, int $days, ?string $reason = null): void
    {
        $this->assertCanModerate($actor, $target);

        if ($days < 0) {
            throw new ValidationException(['banDays' => 'Invalid ban duration.']);
        }

        $until = $days === 0
            ? Carbon::create(2099, 1, 1)
            : Carbon::now()->addDays($days);

        $target->suspended_until = $until;

        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'suspend_reason')) {
            $target->suspend_reason = $reason ?: null;
            $target->suspend_message = $reason ?: null;
        }

        $target->save();

        if (class_exists(\Flarum\Suspend\Event\Suspended::class)) {
            $this->events->dispatch(new \Flarum\Suspend\Event\Suspended($target, $actor));
        }
    }
}
