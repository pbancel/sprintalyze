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
        Schema::create('monitored_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jira_connection_id')->constrained('jira_connections')->onDelete('cascade');
            $table->string('jira_account_id'); // Jira's unique user ID
            $table->string('email');
            $table->string('display_name');
            $table->string('avatar_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure we don't monitor the same user twice per connection
            $table->unique(['jira_connection_id', 'jira_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitored_users');
    }
};
