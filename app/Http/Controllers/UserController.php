<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->get('q');

        $sort = in_array($request->get('sort'), ['active', 'name'])
            ? $request->get('sort')
            : 'active';

        return Inertia::render('Users/Index', [
            'users' => Inertia::scroll(fn (): LengthAwarePaginator => User::query()
                ->where('is_bot', false)
                ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->withCount('messages')
                ->when(
                    $sort === 'active',
                    fn ($query) => $query->orderByDesc('messages_count')->orderByRaw('name COLLATE NOCASE asc'),
                    fn ($query) => $query->orderByRaw('name COLLATE NOCASE asc'),
                )
                ->paginate(50)
                ->withQueryString()),
            'filters' => [
                'q' => $search,
                'sort' => $sort,
            ],
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        return Inertia::render('Users/Show', [
            'user' => $user->loadCount('messages'),
            'messages' => Inertia::scroll(fn (): LengthAwarePaginator => $user->messages()
                ->with(['user', 'channel', 'parent.user'])
                ->withCount('children')
                ->when($request->integer('channel_id') > 0, function (Builder $query) use ($request): Builder {
                    return $query->where('channel_id', $request->integer('channel_id'));
                })
                ->when($request->from_date, function (Builder $query) use ($request): Builder {
                    return $query->where('slack_timestamp', '>=', $request->from_date);
                })
                ->when($request->to_date, function (Builder $query) use ($request): Builder {
                    return $query->where('slack_timestamp', '<=', $request->to_date.' 23:59:59');
                })
                ->orderByDesc('slack_timestamp')
                ->orderByDesc('id')
                ->paginate(25)
                ->withQueryString()),
            'filters' => [
                'channel_id' => $request->channel_id,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
            ],
        ]);
    }
}
