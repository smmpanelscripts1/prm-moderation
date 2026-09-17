<?php

namespace Prm\Moderation\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicDiscussionSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;
use InvalidArgumentException;
use Prm\Moderation\Warning;

class WarningSerializer extends AbstractSerializer
{
    protected $type = 'moderation-warnings';

    protected function getDefaultAttributes($warning)
    {
        if (! ($warning instanceof Warning)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Warning::class
            );
        }

        return [
            'points' => (int) $warning->points,
            'reason' => $warning->reason,
            'comment' => $warning->comment,
            'createdAt' => $this->formatDate($warning->created_at),
        ];
    }

    protected function user($warning)
    {
        return $this->hasOne($warning, BasicUserSerializer::class);
    }

    protected function actor($warning)
    {
        return $this->hasOne($warning, BasicUserSerializer::class);
    }

    protected function discussion($warning)
    {
        return $this->hasOne($warning, BasicDiscussionSerializer::class);
    }
}
