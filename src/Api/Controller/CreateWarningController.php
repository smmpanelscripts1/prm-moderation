<?php

namespace Prm\Moderation\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Prm\Moderation\Api\Serializer\WarningSerializer;
use Prm\Moderation\ModerationActions;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateWarningController extends AbstractCreateController
{
    public $serializer = WarningSerializer::class;

    public $include = ['user', 'actor', 'discussion'];

    /**
     * @var ModerationActions
     */
    protected $actions;

    public function __construct(ModerationActions $actions)
    {
        $this->actions = $actions;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $data = Arr::get($request->getParsedBody(), 'data', []);
        $attributes = Arr::get($data, 'attributes', []);
        $relationships = Arr::get($data, 'relationships', []);

        $userId = Arr::get($relationships, 'user.data.id');
        $target = User::query()->findOrFail($userId);

        $post = null;
        $discussion = null;

        if ($postId = Arr::get($relationships, 'post.data.id')) {
            $post = Post::query()->find($postId);
            $discussion = $post ? $post->discussion : null;
        } elseif ($discussionId = Arr::get($relationships, 'discussion.data.id')) {
            $discussion = Discussion::query()->find($discussionId);
        }

        $warning = $this->actions->warn(
            $actor,
            $target,
            (int) Arr::get($attributes, 'points', 1),
            (string) Arr::get($attributes, 'reason', ''),
            Arr::get($attributes, 'comment'),
            $post,
            $discussion
        );

        $warning->load(['user', 'actor', 'discussion']);

        return $warning;
    }
}
