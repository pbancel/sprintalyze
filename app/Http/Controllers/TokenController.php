<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TokenController extends Controller
{
    /**
     * Obtain tokens for a user by email.
     * This endpoint is for development purposes only to transfer tokens from preproduction to local.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function obtain(Request $request): JsonResponse
    {
        $request->validate([
            'mail' => 'required|email'
        ]);

        $email = $request->input('mail');

        // Find user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }

        // Get the first Jira connection for this user
        $jiraConnection = $user->jiraConnections()->first();

        if (!$jiraConnection) {
            return response()->json([
                'error' => 'No Jira connection found for this user'
            ], 404);
        }

        // Return the decrypted tokens
        return response()->json([
            'access_token' => $jiraConnection->decrypted_access_token,
            'refresh_token' => $jiraConnection->decrypted_refresh_token,
            'expires_at' => $jiraConnection->expires_at,
            'cloud_id' => $jiraConnection->cloud_id,
            'site_url' => $jiraConnection->site_url,
            'site_name' => $jiraConnection->site_name,
        ]);
    }
}
