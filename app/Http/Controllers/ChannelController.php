<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChannelController extends Controller
{
    public function index(Request $request, Channel $channel): Response
    {
        $sortDirection = in_array($request->get('sort_direction'), ['asc', 'desc'])
            ? $request->get('sort_direction')
            : 'desc';

        $messages = $channel->messages()
            ->whereNull('parent_id')
            ->with('user')
            ->withCount('children')
            ->when($request->get('date'), fn ($q, $date) => $q->whereDate('slack_timestamp', $date))
            ->when($request->boolean('pinned'), fn ($q) => $q->where('is_pinned', true))
            ->orderBy('slack_timestamp', $sortDirection)
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Channels/Index', [
            'channel' => $channel,
            'messages' => $messages,
            'sortDirection' => $sortDirection,
            'filters' => [
                'date' => $request->get('date'),
                'pinned' => $request->boolean('pinned'),
            ],
            'dateRange' => Inertia::defer(fn () => [
                'min' => $channel->messages()->min('slack_timestamp'),
                'max' => $channel->messages()->max('slack_timestamp'),
            ]),
        ]);
    }
}
