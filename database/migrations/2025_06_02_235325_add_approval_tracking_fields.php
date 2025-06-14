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
            $table->string('approved_by_email')->nullable()->after('approved_at');
            $table->string('declined_by_email')->nullable()->after('approved_by_email');
            $table->string('declined_by_name')->nullable()->after('declined_by_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            $table->dropColumn(['approved_by_email', 'declined_by_email', 'declined_by_name']);
        });
    }
};
