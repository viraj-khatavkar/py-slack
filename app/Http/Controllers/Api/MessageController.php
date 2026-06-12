<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    public function show(Message $message): JsonResponse
    {
        return response()->json(
            $message->load(['user', 'channel'])->loadCount('children')
        );
    }
}
