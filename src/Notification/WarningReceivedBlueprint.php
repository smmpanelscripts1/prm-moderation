<?php

namespace Prm\Moderation\Notification;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;
use Prm\Moderation\Warning;

class WarningReceivedBlueprint implements BlueprintInterface
{
    /**
     * @var Warning
     */
    public $warning;

    public function __construct(Warning $warning)
    {
        $this->warning = $warning;
    }

    public function getFromUser()
    {
        return $this->warning->actor;
    }

    public function getSubject()
    {
        return $this->warning->user;
    }

    public function getData()
    {
        return [
            'warningId' => $this->warning->id,
            'points' => (int) $this->warning->points,
            'reason' => $this->warning->reason,
        ];
    }

    public static function getType()
    {
        return 'moderationWarningReceived';
    }

    public static function getSubjectModel()
    {
        return User::class;
    }
}
