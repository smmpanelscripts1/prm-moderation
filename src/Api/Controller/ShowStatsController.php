<?php

namespace Prm\Moderation\Api\Controller;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\Post\CommentPost;
use Illuminate\Database\Eloquent\Builder;
use Laminas\Diactoros\Response\JsonResponse;
use Prm\Moderation\Report;
use Prm\Moderation\Warning;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ShowStatsController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertPermission('moderation.access');

        $days = [];
        for ($i = 29; $i >= 0; $i--) {
            $days[] = Carbon::now()->subDays($i)->toDateString();
        }

        $start = Carbon::now()->subDays(29)->startOfDay();

        return new JsonResponse([
            'days' => $days,
            'discussions' => $this->series(
                Discussion::query()->where('is_private', false),
                $start,
                $days
            ),
            'replies' => $this->series(
                CommentPost::query()
                    ->where('is_private', false)
                    ->where('number', '>', 1),
                $start,
                $days
            ),
            'reports' => $this->series(Report::query(), $start, $days),
            'warnings' => $this->series(Warning::query(), $start, $days),
        ]);
    }

    /**
     * @param Builder $query
     * @param string[] $days
     * @return int[]
     */
    private function series(Builder $query, Carbon $start, array $days): array
    {
        $map = $query
            ->where('created_at', '>=', $start)
            ->reorder()
            ->toBase()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $out = [];
        foreach ($days as $day) {
            $out[] = (int) ($map[$day] ?? 0);
        }

        return $out;
    }
}
