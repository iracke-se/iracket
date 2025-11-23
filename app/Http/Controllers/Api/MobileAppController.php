<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileAppController extends Controller
{
    public function storeFcmToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'fcm_token' => 'required|string|max:500',
            'device_type' => 'required|in:android,ios',
            'device_id' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $user->update([
            'fcm_token' => $validated['fcm_token'],
            'device_type' => $validated['device_type'],
            'fcm_token_updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'FCM token stored successfully',
            'user_id' => $user->id,
        ]);
    }

    public function removeFcmToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $user->update([
            'fcm_token' => null,
            'device_type' => null,
            'fcm_token_updated_at' => null,
        ]);

        return response()->json([
            'message' => 'FCM token removed successfully',
        ]);
    }
}
