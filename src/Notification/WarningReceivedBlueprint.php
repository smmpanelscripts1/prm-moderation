<?php

namespace Prm\Moderation\Notification;

use Flarum\Database\AbstractModel;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;
use Prm\Moderation\Warning;

class WarningReceivedBlueprint implements BlueprintInterface, AlertableInterface
{
    public function __construct(
        public Warning $warning
    ) {
    }

    public function getFromUser(): ?User
    {
        return $this->warning->actor;
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->warning->user;
    }

    public function getData(): mixed
    {
        return [
            'warningId' => $this->warning->id,
            'points' => (int) $this->warning->points,
            'reason' => $this->warning->reason,
        ];
    }

    public static function getType(): string
    {
        return 'moderationWarningReceived';
    }

    public static function getSubjectModel(): string
    {
        return User::class;
    }
}
