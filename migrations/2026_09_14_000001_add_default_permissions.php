<?php

use Flarum\Database\Migration;
use Flarum\Group\Group;

return Migration::addPermissions([
    'moderation.access' => Group::MODERATOR_ID,
    'moderation.report' => Group::MEMBER_ID,
]);
