<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update old conversation_user records that don't have joined_at
        // Set joined_at to conversation's created_at so original participants see all messages
        // This ensures original participants can see all historical messages
        DB::statement("
            UPDATE conversation_user cu
            INNER JOIN conversations c ON cu.conversation_id = c.id
            SET cu.joined_at = c.created_at
            WHERE cu.joined_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be perfectly reversed
        // But we can set joined_at to null for records that match conversation created_at
        DB::statement("
            UPDATE conversation_user cu
            INNER JOIN conversations c ON cu.conversation_id = c.id
            SET cu.joined_at = NULL
            WHERE cu.joined_at = c.created_at
        ");
    }
};
