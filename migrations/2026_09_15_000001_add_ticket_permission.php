<?php

use Flarum\Database\Migration;
use Flarum\Group\Group;

return Migration::addPermissions([
    'moderation.ticket' => Group::MEMBER_ID,
]);
