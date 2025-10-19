<?php

namespace App\Services;

use App\Models\JiraConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JiraOAuthService
{
    /**
     * Generate authorization URL
     */
    public function getAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'audience' => 'api.atlassian.com',
            'client_id' => config('jira.oauth.client_id'),
            'scope' => implode(' ', config('jira.oauth.scopes')),
            'redirect_uri' => config('jira.oauth.redirect_uri'),
            'state' => $state,
            'response_type' => 'code',
            'prompt' => 'consent',
        ]);

        return config('jira.oauth.authorize_url') . '?' . $params;
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken(string $code): ?array
    {
        try {
            $response = Http::asForm()->post(config('jira.oauth.token_url'), [
                'grant_type' => 'authorization_code',
                'client_id' => config('jira.oauth.client_id'),
                'client_secret' => config('jira.oauth.client_secret'),
                'code' => $code,
                'redirect_uri' => config('jira.oauth.redirect_uri'),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to exchange code for token', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception during token exchange: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get accessible resources (Jira sites)
     */
    public function getAccessibleResources(string $accessToken): ?array
    {
        try {
            $response = Http::withToken($accessToken)
                ->get(config('jira.oauth.resources_url'));

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to get accessible resources', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception getting accessible resources: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get Jira user information
     */
    public function getJiraUserInfo(string $accessToken, string $cloudId): ?array
    {
        try {
            $response = Http::withToken($accessToken)
                ->get("https://api.atlassian.com/ex/jira/{$cloudId}/rest/api/3/myself");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to get Jira user info', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception getting Jira user info: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Refresh access token
     */
    public function refreshAccessToken(JiraConnection $connection): bool
    {
        try {
            $response = Http::asForm()->post(config('jira.oauth.token_url'), [
                'grant_type' => 'refresh_token',
                'client_id' => config('jira.oauth.client_id'),
                'client_secret' => config('jira.oauth.client_secret'),
                'refresh_token' => $connection->decrypted_refresh_token,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $connection->update([
                    'access_token' => $data['access_token'],
                    'expires_at' => now()->addSeconds($data['expires_in']),
                ]);

                return true;
            }

            Log::error('Failed to refresh token', [
                'connection_id' => $connection->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Exception refreshing token: ' . $e->getMessage(), [
                'connection_id' => $connection->id,
            ]);
            return false;
        }
    }

    /**
     * Store connection details
     */
    public function storeConnection(int $userId, array $tokenData, array $resource): JiraConnection
    {
        return JiraConnection::updateOrCreate(
            [
                'user_id' => $userId,
                'cloud_id' => $resource['id'],
            ],
            [
                'site_url' => $resource['url'],
                'site_name' => $resource['name'] ?? null,
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'expires_at' => now()->addSeconds($tokenData['expires_in']),
            ]
        );
    }

    /**
     * Generate a random state for CSRF protection
     */
    public function generateState(): string
    {
        return Str::random(40);
    }
}
