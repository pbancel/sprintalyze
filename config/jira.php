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

];
