<?php

namespace Prm\Moderation\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Prm\Moderation\Api\Serializer\ReportSerializer;
use Prm\Moderation\Report;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListReportsController extends AbstractListController
{
    public $serializer = ReportSerializer::class;

    public $include = ['reporter', 'targetUser', 'handledBy', 'post', 'discussion'];

    public $limit = 20;

    public $maxLimit = 50;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertPermission('moderation.access');

        $filters = $this->extractFilter($request);
        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $include = $this->extractInclude($request);

        $status = Arr::get($filters, 'status', Report::STATUS_PENDING);

        $query = Report::query()->orderByDesc('created_at');

        if (in_array($status, [Report::STATUS_PENDING, Report::STATUS_RESOLVED, Report::STATUS_REJECTED], true)) {
            $query->where('status', $status);
        }

        $total = (clone $query)->count();
        $results = $query->skip($offset)->take($limit)->get();

        $document->setMeta(['total' => $total]);

        $this->loadRelations($results, $include);

        return $results;
    }
}
