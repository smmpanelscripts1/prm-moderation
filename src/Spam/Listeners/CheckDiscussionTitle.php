<?php

namespace Prm\Moderation\Spam\Listeners;

use Flarum\Discussion\Event\Started;
use Illuminate\Contracts\Events\Dispatcher;
use Prm\Moderation\Spam\Analyzer;
use Prm\Moderation\Spam\SpamActions;

class CheckDiscussionTitle
{
    public function __construct(
        protected Analyzer $analyzer,
        protected SpamActions $actions
    ) {
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Started::class, [$this, 'check']);
    }

    public function check(Started $event): void
    {
        $discussion = $event->discussion;
        $actor = $event->actor;

        if ($actor->isAdmin() || $actor->can('discussion.hide') || $actor->hasPermission('moderation.access')) {
            return;
        }

        $title = (string) ($discussion->title ?? '');
        if (trim($title) === '') {
            return;
        }

        $result = $this->analyzer->analyze($title, $actor, [
            'type' => 'discussion_title',
            'discussion_id' => $discussion->id,
        ]);

        if ($result->getTotalScore() <= 0) {
            return;
        }

        $this->actions->logDetection($actor, $result, 'discussion title', ['title' => $title]);

        if ($result->shouldUnapprove()) {
            $this->actions->unapprove($discussion);
        }

        $firstPost = $discussion->firstPost;
        if (! $firstPost) {
            return;
        }

        if ($result->shouldFlag()) {
            $this->actions->flagPost($firstPost, $result, 'in discussion title');
        }

        if ($result->shouldReport()) {
            $this->actions->createReport($firstPost, $actor, $result, 'in discussion title');
        }
    }
}
