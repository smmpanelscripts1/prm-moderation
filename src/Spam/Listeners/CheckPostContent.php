<?php

namespace Prm\Moderation\Spam\Listeners;

use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Revised;
use Flarum\Post\Event\Saving;
use Illuminate\Contracts\Events\Dispatcher;
use Prm\Moderation\Spam\AnalysisResult;
use Prm\Moderation\Spam\Analyzer;
use Prm\Moderation\Spam\SpamActions;

class CheckPostContent
{
    /** @var array<int, AnalysisResult> */
    private array $pending = [];

    public function __construct(
        protected Analyzer $analyzer,
        protected SpamActions $actions
    ) {
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Saving::class, [$this, 'analyze']);
        $events->listen(Posted::class, [$this, 'afterSave']);
        $events->listen(Revised::class, [$this, 'afterSave']);
    }

    public function analyze(Saving $event): void
    {
        $post = $event->post;
        $actor = $event->actor;

        if ($post->type !== null && $post->type !== 'comment') {
            return;
        }

        if ($post->exists && ! $post->isDirty('content')) {
            return;
        }

        if ($actor->isAdmin() || $actor->can('discussion.hide') || $actor->hasPermission('moderation.access')) {
            return;
        }

        $content = (string) ($post->content ?? '');
        if (trim($content) === '') {
            return;
        }

        $result = $this->analyzer->analyze($content, $actor, [
            'type' => 'post',
            'post_id' => $post->id,
            'discussion_id' => $post->discussion_id,
        ]);

        if ($result->getTotalScore() <= 0) {
            return;
        }

        $this->actions->logDetection($actor, $result, 'post');
        $this->pending[spl_object_id($post)] = $result;

        if ($result->shouldUnapprove()) {
            $this->actions->unapprove($post);
        }
    }

    public function afterSave(Posted|Revised $event): void
    {
        $post = $event->post;
        $id = spl_object_id($post);

        if (! isset($this->pending[$id])) {
            return;
        }

        $result = $this->pending[$id];
        unset($this->pending[$id]);

        $author = $post->user;
        if (! $author) {
            return;
        }

        if ($result->shouldFlag()) {
            $this->actions->flagPost($post, $result);
        }

        if ($result->shouldReport()) {
            $this->actions->createReport($post, $author, $result);
        }
    }
}
