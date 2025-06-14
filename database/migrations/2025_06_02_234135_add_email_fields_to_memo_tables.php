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
        Schema::table('memos', function (Blueprint $table) {
            $table->string('raised_by_email')->nullable()->after('raised_by');
            $table->string('raised_by_name')->nullable()->after('raised_by_email');
        });

        Schema::table('approvals', function (Blueprint $table) {
            $table->string('user_email')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memos', function (Blueprint $table) {
            $table->dropColumn(['raised_by_email', 'raised_by_name']);
        });

        Schema::table('approvals', function (Blueprint $table) {
            $table->dropColumn('user_email');
        });
    }
};
