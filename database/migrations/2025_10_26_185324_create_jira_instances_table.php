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
        Schema::create('jira_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('jira_connection_id')->constrained('jira_connections')->onDelete('cascade');
            $table->string('cloud_id'); // Jira's unique cloud ID
            $table->string('site_name');
            $table->string('site_url');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure we don't monitor the same instance twice per user
            $table->unique(['user_id', 'jira_connection_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_instances');
    }
};
