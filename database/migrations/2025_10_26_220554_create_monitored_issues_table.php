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
        Schema::create('monitored_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jira_connection_id')->constrained('jira_connections')->onDelete('cascade');
            $table->string('issue_key'); // e.g., "PROJ-123"
            $table->string('issue_id'); // Jira's unique issue ID
            $table->string('summary');
            $table->string('issue_type')->nullable();
            $table->string('status')->nullable();
            $table->string('assignee_id')->nullable();
            $table->string('assignee_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure we don't monitor the same issue twice per connection
            $table->unique(['jira_connection_id', 'issue_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitored_issues');
    }
};
