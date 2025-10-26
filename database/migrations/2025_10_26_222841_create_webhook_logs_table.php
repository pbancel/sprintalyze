<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type')->nullable(); // e.g., 'jira:issue_updated', 'jira:issue_created'
            $table->string('webhook_id')->nullable(); // Jira webhook ID if available
            $table->string('issue_key')->nullable(); // Issue key if applicable
            $table->text('headers')->nullable(); // HTTP headers as JSON
            $table->longText('payload'); // Full webhook payload as JSON
            $table->ipAddress('ip_address')->nullable(); // Source IP address
            $table->timestamps();

            // Add indexes for common queries
            $table->index('event_type');
            $table->index('issue_key');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
