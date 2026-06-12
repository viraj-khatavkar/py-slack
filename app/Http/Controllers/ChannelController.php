<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChannelController extends Controller
{
    public function index(Request $request, Channel $channel): Response|RedirectResponse
    {
        $sortDirection = in_array($request->get('sort_direction'), ['asc', 'desc'])
            ? $request->get('sort_direction')
            : 'desc';

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->get('jump_date'))) {
            return $this->jumpToDate($request, $channel, $sortDirection);
        }

        if ($request->integer('jump_msg') > 0) {
            return $this->jumpToMessage($request, $channel, $sortDirection);
        }

        return Inertia::render('Channels/Index', [
            'channel' => $channel,
            'messages' => Inertia::scroll(fn (): LengthAwarePaginator => $this->topLevelMessages($request, $channel)
                ->with('user')
                ->withCount('children')
                ->when($request->get('date'), fn ($q, $date) => $q->whereDate('slack_timestamp', $date))
                ->orderBy('slack_timestamp', $sortDirection)
                ->orderBy('id', $sortDirection)
                ->paginate(25)
                ->withQueryString()),
            'sortDirection' => $sortDirection,
            'goto' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->get('goto'))
                ? $request->get('goto')
                : null,
            'gotoMessage' => $request->integer('goto_msg') > 0
                ? $request->integer('goto_msg')
                : null,
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

    protected function jumpToDate(Request $request, Channel $channel, string $sortDirection): RedirectResponse
    {
        $date = $request->get('jump_date');

        $total = $this->topLevelMessages($request, $channel)->count();

        $messagesBefore = $this->topLevelMessages($request, $channel)
            ->when(
                $sortDirection === 'asc',
                fn ($q) => $q->where('slack_timestamp', '<', $date.' 00:00:00'),
                fn ($q) => $q->where('slack_timestamp', '>', $date.' 23:59:59'),
            )
            ->count();

        $page = intdiv(min($messagesBefore, max($total - 1, 0)), 25) + 1;

        return redirect()->route('channels.index', array_filter([
            'channel' => $channel->name,
            'page' => $page > 1 ? $page : null,
            'sort_direction' => $sortDirection,
            'pinned' => $request->boolean('pinned') ? 1 : null,
            'goto' => $date,
        ]));
    }

    protected function jumpToMessage(Request $request, Channel $channel, string $sortDirection): RedirectResponse
    {
        $message = $this->topLevelMessages($request, $channel)->find($request->integer('jump_msg'));

        if (! $message) {
            return redirect()->route('channels.index', ['channel' => $channel->name]);
        }

        $messagesBefore = $this->topLevelMessages($request, $channel)
            ->where(function (Builder $query) use ($message, $sortDirection): void {
                $operator = $sortDirection === 'asc' ? '<' : '>';

                $query->where('slack_timestamp', $operator, $message->slack_timestamp)
                    ->orWhere(function (Builder $query) use ($message, $operator): void {
                        $query->where('slack_timestamp', $message->slack_timestamp)
                            ->where('id', $operator, $message->id);
                    });
            })
            ->count();

        $page = intdiv($messagesBefore, 25) + 1;

        return redirect()->route('channels.index', array_filter([
            'channel' => $channel->name,
            'page' => $page > 1 ? $page : null,
            'sort_direction' => $sortDirection,
            'pinned' => $request->boolean('pinned') ? 1 : null,
            'goto_msg' => $message->id,
        ]));
    }

    /** @return HasMany<\App\Models\Message, Channel> */
    protected function topLevelMessages(Request $request, Channel $channel): HasMany
    {
        return $channel->messages()
            ->whereNull('parent_id')
            ->when($request->boolean('pinned'), fn ($q) => $q->where('is_pinned', true));
    }
}
