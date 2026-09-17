<?php

namespace Prm\Moderation;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\User\User;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int $user_id
 * @property string $content
 * @property bool $is_staff
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Ticket $ticket
 * @property-read User $user
 */
class TicketReply extends AbstractModel
{
    protected $table = 'moderation_ticket_replies';

    protected $guarded = [];

    public $timestamps = true;

    protected $dates = ['created_at', 'updated_at'];

    protected $casts = [
        'is_staff' => 'boolean',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
