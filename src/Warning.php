<?php

namespace Prm\Moderation;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\User\User;

/**
 * @property int $id
 * @property int $user_id
 * @property int $actor_id
 * @property int $points
 * @property string $reason
 * @property string|null $comment
 * @property int|null $post_id
 * @property int|null $discussion_id
 * @property int|null $report_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read User $user
 * @property-read User $actor
 * @property-read Post|null $post
 * @property-read Discussion|null $discussion
 * @property-read Report|null $report
 */
class Warning extends AbstractModel
{
    protected $table = 'moderation_warnings';

    protected $guarded = [];

    public $timestamps = true;

    protected $dates = ['created_at', 'updated_at'];

    protected $casts = [
        'points' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function discussion()
    {
        return $this->belongsTo(Discussion::class);
    }

    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}
