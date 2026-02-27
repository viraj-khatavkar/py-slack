<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->get('q');

        return Inertia::render('Users/Index', [
            'users' => User::query()
                ->where('is_bot', false)
                ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->withCount('messages')
                ->orderBy('name')
                ->paginate(50)
                ->withQueryString(),
            'filters' => [
                'q' => $search,
            ],
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $messages = $user->messages()
            ->with(['user', 'channel', 'parent.user'])
            ->withCount('children')
            ->orderByDesc('slack_timestamp')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Users/Show', [
            'user' => $user->loadCount('messages'),
            'messages' => $messages,
        ]);
    }
}
