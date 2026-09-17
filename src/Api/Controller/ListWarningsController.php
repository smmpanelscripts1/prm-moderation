<?php

namespace Prm\Moderation\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;
use Prm\Moderation\Api\Serializer\WarningSerializer;
use Prm\Moderation\Warning;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListWarningsController extends AbstractListController
{
    public $serializer = WarningSerializer::class;

    public $include = ['user', 'actor', 'discussion'];

    public $limit = 20;

    public $maxLimit = 50;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $filters = $this->extractFilter($request);
        $userId = (int) Arr::get($filters, 'user');

        if (! $userId) {
            throw new PermissionDeniedException();
        }

        if ((int) $actor->id !== $userId) {
            $actor->assertPermission('moderation.access');
        } else {
            $actor->assertRegistered();
        }

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $include = $this->extractInclude($request);

        $query = Warning::query()->where('user_id', $userId)->orderByDesc('created_at');

        $total = (clone $query)->count();
        $results = $query->skip($offset)->take($limit)->get();

        $document->setMeta(['total' => $total]);

        $this->loadRelations($results, $include);

        return $results;
    }
}
