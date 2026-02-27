<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $messages = null;

        if ($request->filled('q')) {
            $escapedQuery = str_replace(['%', '_'], ['\\%', '\\_'], $request->q);

            $sortBy = in_array($request->get('sort_by'), ['slack_timestamp', 'children_count'])
                ? $request->get('sort_by')
                : 'slack_timestamp';

            $sortDirection = in_array($request->get('sort_direction'), ['asc', 'desc'])
                ? $request->get('sort_direction')
                : 'desc';

            $query = Message::query()
                ->where('content', 'like', '%'.$escapedQuery.'%')
                ->when($request->from_date, function (Builder $query) use ($request): Builder {
                    return $query->where('slack_timestamp', '>=', $request->from_date);
                })
                ->when($request->to_date, function (Builder $query) use ($request): Builder {
                    return $query->where('slack_timestamp', '<=', $request->to_date);
                })
                ->when($request->channel_id && $request->channel_id > 0, function (Builder $query) use ($request): Builder {
                    return $query->where('channel_id', $request->channel_id);
                })
                ->with('user', 'channel', 'parent.user')
                ->withCount('children');

            if ($sortBy === 'children_count') {
                $query->whereNull('parent_id')
                    ->orderBy('children_count', $sortDirection);
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }

            $messages = $query->paginate(25)->withQueryString();
        }

        return Inertia::render('Search/Index', [
            'messages' => $messages,
            'filters' => [
                'q' => $request->q,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'channel_id' => $request->channel_id,
                'sort_by' => $request->get('sort_by', 'slack_timestamp'),
                'sort_direction' => $request->get('sort_direction', 'desc'),
            ],
        ]);
    }
}
