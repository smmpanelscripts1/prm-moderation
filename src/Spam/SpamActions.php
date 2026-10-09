<?php

namespace Prm\Moderation\Spam;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extension\ExtensionManager;
use Flarum\Flags\Flag;
use Flarum\Post\Post;
use Flarum\User\User;
use Prm\Moderation\Report;
use Psr\Log\LoggerInterface;

class SpamActions
{
    public function __construct(
        protected SpamConfig $config,
        protected ExtensionManager $extensions,
        protected LoggerInterface $log
    ) {
    }

    public function getSystemActor(): ?User
    {
        return User::query()->find($this->config->systemUserId());
    }

    public function unapprove(Discussion|Post $entity): void
    {
        if (! $this->extensions->isEnabled('flarum-approval')) {
            return;
        }

        if (! isset($entity->is_approved)) {
            return;
        }

        $entity->is_approved = false;

        if ($entity instanceof Post) {
            $entity->afterSave(function (Post $post) {
                if ((int) $post->number === 1 && $post->discussion && $post->discussion->is_approved) {
                    $post->discussion->is_approved = false;
                    $post->discussion->save();
                }
            });

            return;
        }

        if ($entity->exists) {
            $entity->save();

            $firstPost = $entity->firstPost;
            if ($firstPost && $firstPost->is_approved) {
                $firstPost->is_approved = false;
                $firstPost->save();
            }
        }
    }

    public function flagPost(Post $post, AnalysisResult $result, ?string $reasonPrefix = null): void
    {
        if (! $this->extensions->isEnabled('flarum-flags') || ! class_exists(Flag::class)) {
            return;
        }

        if (Flag::query()->where('post_id', $post->id)->where('type', 'spam')->exists()) {
            return;
        }

        $actor = $this->getSystemActor();
        if (! $actor) {
            return;
        }

        $detail = implode("\n", $result->getAllReasons());
        if ($reasonPrefix) {
            $detail = "Detected {$reasonPrefix}:\n\n".$detail;
        }

        $flag = new Flag();
        $flag->type = 'spam';
        $flag->user_id = $actor->id;
        $flag->post_id = $post->id;
        $flag->reason = (string) $result->getTotalScore();
        $flag->reason_detail = $detail;
        $flag->created_at = Carbon::now();
        $flag->save();
    }

    public function createReport(Post $post, User $target, AnalysisResult $result, ?string $reasonPrefix = null): void
    {
        $reporter = $this->getSystemActor();
        if (! $reporter) {
            return;
        }

        $exists = Report::query()
            ->where('status', Report::STATUS_PENDING)
            ->where('reason', 'spam')
            ->where('post_id', $post->id)
            ->exists();

        if ($exists) {
            return;
        }

        $detail = implode("\n", $result->getAllReasons());
        if ($reasonPrefix) {
            $detail = "Detected {$reasonPrefix}:\n\n".$detail;
        }
        $detail = trim("Auto-detected spam (score {$result->getTotalScore()}).\n\n".$detail);

        $report = new Report();
        $report->reporter_id = $reporter->id;
        $report->target_user_id = $target->id;
        $report->target_type = Report::TYPE_POST;
        $report->post_id = $post->id;
        $report->discussion_id = $post->discussion_id;
        $report->reason = 'spam';
        $report->reason_detail = mb_substr($detail, 0, 5000);
        $report->status = Report::STATUS_PENDING;
        $report->save();
    }

    public function logDetection(User $actor, AnalysisResult $result, string $contentType, array $extra = []): void
    {
        $this->log->info(
            "[PRM Moderation] Spam indicators in {$contentType} by {$actor->username} (#{$actor->id})",
            array_merge([
                'score' => $result->getTotalScore(),
                'reasons' => $result->getAllReasons(),
                'flag' => $result->shouldFlag(),
                'unapprove' => $result->shouldUnapprove(),
                'report' => $result->shouldReport(),
            ], $extra)
        );
    }
}
