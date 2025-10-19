<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Jira Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for your Jira instance. This should be in the format:
    | https://your-domain.atlassian.net
    |
    */

    'base_url' => env('JIRA_BASE_URL', 'https://your-domain.atlassian.net'),

    /*
    |--------------------------------------------------------------------------
    | Jira Email
    |--------------------------------------------------------------------------
    |
    | The email address associated with your Jira account.
    |
    */

    'email' => env('JIRA_EMAIL', ''),

    /*
    |--------------------------------------------------------------------------
    | Jira API Token
    |--------------------------------------------------------------------------
    |
    | Your Jira API token. You can generate one from:
    | https://id.atlassian.com/manage-profile/security/api-tokens
    |
    */

    'api_token' => env('JIRA_API_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | OAuth 2.0 Configuration
    |--------------------------------------------------------------------------
    |
    | OAuth 2.0 settings for Jira Cloud integration
    |
    */

    'oauth' => [
        'client_id' => env('JIRA_CLIENT_ID', ''),
        'client_secret' => env('JIRA_CLIENT_SECRET', ''),
        'redirect_uri' => env('APP_URL', 'http://localhost') . '/jira/callback',
        'authorize_url' => 'https://auth.atlassian.com/authorize',
        'token_url' => 'https://auth.atlassian.com/oauth/token',
        'resources_url' => 'https://api.atlassian.com/oauth/token/accessible-resources',
        'scopes' => [
            'read:jira-work',
            'read:jira-user',
            'write:jira-work',
            'offline_access', // Required for refresh token
        ],
    ],

];
