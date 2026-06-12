<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $sortBy = in_array($request->get('sort_by'), ['slack_timestamp', 'children_count', 'relevance'])
            ? $request->get('sort_by')
            : 'slack_timestamp';

        $sortDirection = in_array($request->get('sort_direction'), ['asc', 'desc'])
            ? $request->get('sort_direction')
            : 'desc';

        return Inertia::render('Search/Index', [
            'messages' => $request->filled('q')
                ? Inertia::scroll(fn (): LengthAwarePaginator => $this->searchMessages($request, $sortBy, $sortDirection))
                : null,
            'filters' => [
                'q' => $request->q,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'channel_id' => $request->channel_id,
                'user_id' => $request->user_id,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
            'filterUser' => $request->integer('user_id') > 0
                ? User::query()->select(['id', 'name', 'image_url'])->find($request->integer('user_id'))
                : null,
        ]);
    }

    protected function searchMessages(Request $request, string $sortBy, string $sortDirection): LengthAwarePaginator
    {
        if ($this->fullTextSearchAvailable()) {
            try {
                return $this->buildSearchQuery($request, $sortBy, $sortDirection, useFullText: true)
                    ->paginate(25)
                    ->withQueryString();
            } catch (QueryException) {
                // Fall back to LIKE when the FTS query cannot be parsed.
            }
        }

        return $this->buildSearchQuery($request, $sortBy, $sortDirection, useFullText: false)
            ->paginate(25)
            ->withQueryString();
    }

    /** @return Builder<Message> */
    protected function buildSearchQuery(Request $request, string $sortBy, string $sortDirection, bool $useFullText): Builder
    {
        $query = Message::query()->select('messages.*');

        if ($useFullText) {
            $query->join('messages_fts', 'messages_fts.rowid', '=', 'messages.id')
                ->whereRaw('messages_fts MATCH ?', [$this->toFullTextQuery($request->q)]);
        } else {
            $escapedQuery = str_replace(['%', '_'], ['\\%', '\\_'], $request->q);
            $query->where('content', 'like', '%'.$escapedQuery.'%');
        }

        $query
            ->when($request->from_date, function (Builder $query) use ($request): Builder {
                return $query->where('slack_timestamp', '>=', $request->from_date);
            })
            ->when($request->to_date, function (Builder $query) use ($request): Builder {
                return $query->where('slack_timestamp', '<=', $request->to_date.' 23:59:59');
            })
            ->when($request->integer('channel_id') > 0, function (Builder $query) use ($request): Builder {
                return $query->where('channel_id', $request->integer('channel_id'));
            })
            ->when($request->integer('user_id') > 0, function (Builder $query) use ($request): Builder {
                return $query->where('messages.user_id', $request->integer('user_id'));
            })
            ->with(['user', 'channel', 'parent.user'])
            ->withCount('children');

        if ($sortBy === 'children_count') {
            $query->whereNull('parent_id')->orderBy('children_count', $sortDirection);
        } elseif ($sortBy === 'relevance' && $useFullText) {
            $query->orderByRaw('messages_fts.rank');
        } elseif ($sortBy === 'relevance') {
            $query->orderBy('slack_timestamp', 'desc');
        } else {
            $query->orderBy($sortBy, $sortDirection)->orderBy('messages.id', $sortDirection);
        }

        return $query;
    }

    protected function toFullTextQuery(string $raw): string
    {
        return collect(preg_split('/\s+/', trim($raw)) ?: [])
            ->filter(fn (string $token): bool => $token !== '')
            ->map(fn (string $token): string => '"'.str_replace('"', '""', $token).'"*')
            ->implode(' ');
    }

    protected function fullTextSearchAvailable(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite' && Schema::hasTable('messages_fts');
    }
}
