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
        Schema::create('microsoft_groups', function (Blueprint $table) {
            $table->id();
            $table->string('azure_group_id')->unique(); // Azure AD group ID
            $table->string('display_name'); // Group display name
            $table->text('description')->nullable(); // Group description
            $table->json('members'); // JSON array of group members with their details
            $table->integer('member_count')->default(0); // Quick access to member count
            $table->timestamp('last_synced_at'); // When this group was last synced from Graph API
            $table->timestamps();
            
            // Indexes for performance
            $table->index('display_name');
            $table->index('last_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('microsoft_groups');
    }
}; 