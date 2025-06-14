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
        Schema::table('approvals', function (Blueprint $table) {
            // Remove old individual user-based columns
            $table->dropColumn(['user_id', 'user_email']);
            
            // Add group-based hierarchical columns
            $table->string('required_group_name')->after('memo_id'); // Which group needs to approve
            $table->integer('group_priority')->after('required_group_name'); // Group hierarchy (1=highest)
            
            // Add approved_by_name to match declined_by_name pattern
            $table->string('approved_by_name')->nullable()->after('approved_by_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            // Restore old columns
            $table->bigInteger('user_id')->after('memo_id');
            $table->string('user_email')->nullable()->after('user_id');
            
            // Remove group-based columns
            $table->dropColumn(['required_group_name', 'group_priority', 'approved_by_name']);
        });
    }
};
