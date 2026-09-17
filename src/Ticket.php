<?php

namespace Prm\Moderation;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $assigned_to_id
 * @property string $subject
 * @property string $category
 * @property string $priority
 * @property string $status
 * @property Carbon|null $last_replied_at
 * @property int|null $closed_by_id
 * @property Carbon|null $closed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read User $user
 * @property-read User|null $assignedTo
 * @property-read User|null $closedBy
 * @property-read Collection|TicketReply[] $replies
 */
class Ticket extends AbstractModel
{
    const STATUS_OPEN = 'open';
    const STATUS_WAITING = 'waiting';
    const STATUS_ANSWERED = 'answered';
    const STATUS_CLOSED = 'closed';

    const CATEGORIES = ['account', 'payment', 'technical', 'appeal', 'other'];
    const PRIORITIES = ['normal', 'high'];

    const MAX_OPEN_PER_USER = 8;

    protected $table = 'moderation_tickets';

    protected $guarded = [];

    public $timestamps = true;

    protected $dates = ['created_at', 'updated_at', 'last_replied_at', 'closed_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by_id');
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class)->orderBy('created_at');
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isOpenForStaff(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_WAITING], true);
    }
}
