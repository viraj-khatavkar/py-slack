<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Home', [
            'stats' => Cache::rememberForever('archive-stats', fn (): array => [
                'messages' => Message::count(),
                'threads' => Message::whereNull('parent_id')->where('reply_users_count', '>', 0)->count(),
                'users' => User::where('is_bot', false)->count(),
                'channels' => Channel::count(),
                'oldest' => Message::min('slack_timestamp'),
                'newest' => Message::max('slack_timestamp'),
            ]),
            'channelActivity' => Cache::rememberForever('channel-activity', fn () => Message::query()
                ->selectRaw('channel_id, MIN(slack_timestamp) as first_message_at, MAX(slack_timestamp) as last_message_at')
                ->groupBy('channel_id')
                ->get()
                ->keyBy('channel_id')),
        ]);
    }
}
