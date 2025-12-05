<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixOriginalParticipants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:fix-original-participants';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix joined_at for original participants to ensure they see all messages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing original participants...');

        // Strategy: Set joined_at to conversation.created_at for all original participants
        // This ensures they see all messages from the start
        
        // 1. Update records where joined_at is null
        $updated1 = DB::statement("
            UPDATE conversation_user cu
            INNER JOIN conversations c ON cu.conversation_id = c.id
            SET cu.joined_at = c.created_at
            WHERE cu.joined_at IS NULL
        ");
        $this->info("Updated records with null joined_at");

        // 2. Update records where joined_at is after conversation.created_at
        // but within 10 minutes (likely original participants with timing issues)
        $updated2 = DB::statement("
            UPDATE conversation_user cu
            INNER JOIN conversations c ON cu.conversation_id = c.id
            SET cu.joined_at = c.created_at
            WHERE cu.joined_at > c.created_at 
            AND TIMESTAMPDIFF(SECOND, c.created_at, cu.joined_at) <= 600
        ");
        $this->info("Updated records with joined_at > conversation.created_at (within 10 min)");

        // 3. Also update records where joined_at equals conversation.created_at exactly
        // (already correct, but ensure consistency)
        $this->info("Checking for any remaining issues...");
        
        $remaining = DB::select("
            SELECT COUNT(*) as count
            FROM conversation_user cu
            INNER JOIN conversations c ON cu.conversation_id = c.id
            WHERE cu.joined_at IS NULL
        ");
        
        $this->info("Remaining null joined_at records: " . ($remaining[0]->count ?? 0));
        $this->info('Done! Original participants should now see all messages.');
        
        return Command::SUCCESS;
    }
}

