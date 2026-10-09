<?php

namespace Prm\Moderation\Api\Controller;

use Flarum\Api\Client;
use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Prm\Moderation\ModerationActions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MarkAsSpammerController implements RequestHandlerInterface
{
    public function __construct(
        protected ModerationActions $actions,
        protected Client $api
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $userId = Arr::get($request->getQueryParams(), 'id');
        $user = User::query()->findOrFail($userId);

        $this->actions->markAsSpammer($actor, $user);

        if (! $user->exists) {
            return new EmptyResponse();
        }

        return $this->api->withParentRequest($request)->get('/users/'.$user->id);
    }
}
