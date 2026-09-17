<?php

namespace Prm\Moderation\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Api\Serializer\PostSerializer;
use InvalidArgumentException;
use Prm\Moderation\Report;

class ReportSerializer extends AbstractSerializer
{
    protected $type = 'moderation-reports';

    protected function getDefaultAttributes($report)
    {
        if (! ($report instanceof Report)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Report::class
            );
        }

        return [
            'targetType' => $report->target_type,
            'reason' => $report->reason,
            'reasonDetail' => $report->reason_detail,
            'status' => $report->status,
            'createdAt' => $this->formatDate($report->created_at),
            'handledAt' => $this->formatDate($report->handled_at),
        ];
    }

    protected function reporter($report)
    {
        return $this->hasOne($report, BasicUserSerializer::class);
    }

    protected function targetUser($report)
    {
        return $this->hasOne($report, BasicUserSerializer::class);
    }

    protected function handledBy($report)
    {
        return $this->hasOne($report, BasicUserSerializer::class);
    }

    protected function post($report)
    {
        return $this->hasOne($report, PostSerializer::class);
    }

    protected function discussion($report)
    {
        return $this->hasOne($report, DiscussionSerializer::class);
    }
}
