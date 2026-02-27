<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\JsonResponse;

class MessageChildrenController extends Controller
{
    public function index(Message $message): JsonResponse
    {
        $children = $message->children()
            ->with('user')
            ->orderBy('slack_timestamp', 'asc')
            ->paginate(50);

        return response()->json($children);
    }
}
