<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = str_replace(['%', '_'], ['\\%', '\\_'], (string) $request->get('q', ''));

        return response()->json(
            User::query()
                ->select(['id', 'name', 'image_url'])
                ->where('is_bot', false)
                ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->withCount('messages')
                ->orderByDesc('messages_count')
                ->limit(8)
                ->get()
        );
    }
}
