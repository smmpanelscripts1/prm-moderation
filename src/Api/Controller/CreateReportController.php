<?php

namespace Prm\Moderation\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Flarum\Post\Post;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Prm\Moderation\Api\Serializer\ReportSerializer;
use Prm\Moderation\Report;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateReportController extends AbstractCreateController
{
    public $serializer = ReportSerializer::class;

    public $include = ['reporter', 'targetUser', 'post', 'discussion'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('create', Report::class);

        $data = Arr::get($request->getParsedBody(), 'data', []);
        $attributes = Arr::get($data, 'attributes', []);
        $relationships = Arr::get($data, 'relationships', []);

        $type = Arr::get($attributes, 'targetType');
        if (! in_array($type, [Report::TYPE_USER, Report::TYPE_POST, Report::TYPE_DISCUSSION], true)) {
            throw new ValidationException(['targetType' => 'Invalid report target.']);
        }

        $reason = Arr::get($attributes, 'reason');
        if (! in_array($reason, ['spam', 'abuse', 'illegal', 'other'], true)) {
            throw new ValidationException(['reason' => 'Invalid reason.']);
        }

        $detail = trim((string) Arr::get($attributes, 'reasonDetail', ''));
        if ($reason === 'other' && $detail === '') {
            throw new ValidationException(['reasonDetail' => 'Please explain this report.']);
        }
        if (mb_strlen($detail) > 2000) {
            throw new ValidationException(['reasonDetail' => 'Details are too long.']);
        }

        $post = null;
        $discussion = null;
        $target = null;

        if ($type === Report::TYPE_POST) {
            $postId = Arr::get($relationships, 'post.data.id');
            $post = Post::query()->findOrFail($postId);
            $discussion = $post->discussion;
            $target = $post->user;
        } elseif ($type === Report::TYPE_DISCUSSION) {
            $discussionId = Arr::get($relationships, 'discussion.data.id');
            $discussion = Discussion::query()->findOrFail($discussionId);
            $target = $discussion->user;
        } else {
            $userId = Arr::get($relationships, 'user.data.id') ?: Arr::get($relationships, 'targetUser.data.id');
            $target = User::query()->findOrFail($userId);
        }

        if (! $target) {
            throw new ValidationException(['target' => 'Could not determine the reported user.']);
        }

        if ((int) $target->id === (int) $actor->id) {
            throw new PermissionDeniedException();
        }

        $report = Report::query()->firstOrNew([
            'reporter_id' => $actor->id,
            'target_type' => $type,
            'target_user_id' => $target->id,
            'post_id' => $post ? $post->id : null,
            'discussion_id' => $discussion ? $discussion->id : null,
            'status' => Report::STATUS_PENDING,
        ]);

        $report->reason = $reason;
        $report->reason_detail = $detail !== '' ? $detail : null;
        $report->save();

        $report->load(['reporter', 'targetUser', 'post', 'discussion']);

        return $report;
    }
}
