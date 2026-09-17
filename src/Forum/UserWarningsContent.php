<?php

namespace Prm\Moderation\Forum;

use Flarum\Forum\Content\User as UserContent;
use Flarum\Frontend\Document;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserWarningsContent
{
    /**
     * @var UserContent
     */
    protected $userContent;

    public function __construct(UserContent $userContent)
    {
        $this->userContent = $userContent;
    }

    public function __invoke(Document $document, Request $request)
    {
        $params = $request->getAttribute('routeParameters') ?: [];
        $request = $request->withQueryParams(array_merge($request->getQueryParams(), $params));

        return ($this->userContent)($document, $request);
    }
}
