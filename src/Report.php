<?php

namespace Prm\Moderation;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\User\User;

/**
 * @property int $id
 * @property int $reporter_id
 * @property int $target_user_id
 * @property string $target_type
 * @property int|null $post_id
 * @property int|null $discussion_id
 * @property string $reason
 * @property string|null $reason_detail
 * @property string $status
 * @property int|null $handled_by_id
 * @property Carbon|null $handled_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read User $reporter
 * @property-read User $targetUser
 * @property-read User|null $handledBy
 * @property-read Post|null $post
 * @property-read Discussion|null $discussion
 */
class Report extends AbstractModel
{
    const TYPE_USER = 'user';
    const TYPE_POST = 'post';
    const TYPE_DISCUSSION = 'discussion';

    const STATUS_PENDING = 'pending';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_REJECTED = 'rejected';

    protected $table = 'moderation_reports';

    protected $guarded = [];

    public $timestamps = true;

    protected $dates = ['created_at', 'updated_at', 'handled_at'];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function handledBy()
    {
        return $this->belongsTo(User::class, 'handled_by_id');
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function discussion()
    {
        return $this->belongsTo(Discussion::class);
    }
}
