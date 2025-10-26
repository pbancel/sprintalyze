<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JiraWebhookController extends Controller
{
    /**
     * Handle incoming Jira webhook events
     */
    public function handle(Request $request)
    {
        try {
            // Get the full request payload
            $payload = $request->all();

            // Extract event type from webhook
            $eventType = $request->input('webhookEvent') ?? $request->header('X-Atlassian-Webhook-Identifier');

            // Extract issue key if present
            $issueKey = null;
            if (isset($payload['issue']['key'])) {
                $issueKey = $payload['issue']['key'];
            }

            // Extract webhook ID if present
            $webhookId = $request->header('X-Atlassian-Webhook-Id');

            // Get all headers
            $headers = $request->headers->all();

            // Get client IP
            $ipAddress = $request->ip();

            // Log the webhook event
            Log::info('Jira webhook received', [
                'event_type' => $eventType,
                'issue_key' => $issueKey,
                'webhook_id' => $webhookId,
                'ip_address' => $ipAddress
            ]);

            // Store webhook log in database
            WebhookLog::create([
                'event_type' => $eventType,
                'webhook_id' => $webhookId,
                'issue_key' => $issueKey,
                'headers' => $headers,
                'payload' => $payload,
                'ip_address' => $ipAddress,
            ]);

            // Return success response (Jira expects 2xx response)
            return response()->json([
                'success' => true,
                'message' => 'Webhook received successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to process Jira webhook: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            // Still return 200 to prevent Jira from retrying
            // But log the error for debugging
            return response()->json([
                'success' => false,
                'message' => 'Webhook received but failed to process'
            ], 200);
        }
    }
}
