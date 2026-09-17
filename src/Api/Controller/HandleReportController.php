<?php

namespace Prm\Moderation\Api\Controller;

use Carbon\Carbon;
use Flarum\Api\Controller\AbstractShowController;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Prm\Moderation\Api\Serializer\ReportSerializer;
use Prm\Moderation\ModerationActions;
use Prm\Moderation\Report;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class HandleReportController extends AbstractShowController
{
    public $serializer = ReportSerializer::class;

    public $include = ['reporter', 'targetUser', 'handledBy', 'post', 'discussion'];

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
        $id = Arr::get($request->getQueryParams(), 'id');
        $report = Report::query()->findOrFail($id);

        $actor->assertCan('handle', $report);

        $attributes = Arr::get($request->getParsedBody(), 'data.attributes', []);
        $status = Arr::get($attributes, 'status', Report::STATUS_RESOLVED);

        if (! in_array($status, [Report::STATUS_RESOLVED, Report::STATUS_REJECTED], true)) {
            throw new ValidationException(['status' => 'Invalid status.']);
        }

        $report->load(['targetUser', 'post', 'discussion']);
        $target = $report->targetUser;

        if ($status === Report::STATUS_RESOLVED && $target) {
            if (! empty($attributes['deleteContent'])) {
                $this->actions->hideContent($actor, $report->post, $report->discussion);
            }

            if (! empty($attributes['warn'])) {
                $this->actions->warn(
                    $actor,
                    $target,
                    (int) Arr::get($attributes, 'warningPoints', 1),
                    (string) Arr::get($attributes, 'warningReason', ''),
                    Arr::get($attributes, 'warningComment'),
                    $report->post,
                    $report->discussion,
                    $report
                );
            }

            if (! empty($attributes['ban'])) {
                $this->actions->ban(
                    $actor,
                    $target,
                    (int) Arr::get($attributes, 'banDays', 0),
                    Arr::get($attributes, 'banReason')
                );
            }
        }

        $report->status = $status;
        $report->handled_by_id = $actor->id;
        $report->handled_at = Carbon::now();
        $report->save();

        $report->load(['reporter', 'targetUser', 'handledBy', 'post', 'discussion']);

        return $report;
    }
}
