<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users
        $user1 = User::firstOrCreate(
            ['email' => 'alice@example.com'],
            [
                'name' => 'Alice',
                'password' => Hash::make('password'),
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'bob@example.com'],
            [
                'name' => 'Bob',
                'password' => Hash::make('password'),
            ]
        );

        $user3 = User::firstOrCreate(
            ['email' => 'charlie@example.com'],
            [
                'name' => 'Charlie',
                'password' => Hash::make('password'),
            ]
        );

        // Create a 1-on-1 conversation between Alice and Bob
        $conversation1 = Conversation::firstOrCreate([
            'created_by' => $user1->id,
            'is_group' => false,
        ]);

        if (!$conversation1->users->contains($user1->id)) {
            $conversation1->users()->attach([$user1->id, $user2->id]);
        }

        // Add some messages
        if ($conversation1->messages()->count() === 0) {
            Message::create([
                'conversation_id' => $conversation1->id,
                'user_id' => $user1->id,
                'body' => 'Hey Bob! How are you?',
                'type' => 'text',
            ]);

            Message::create([
                'conversation_id' => $conversation1->id,
                'user_id' => $user2->id,
                'body' => 'Hi Alice! I\'m doing great, thanks for asking!',
                'type' => 'text',
            ]);
        }

        // Create a group conversation
        $conversation2 = Conversation::firstOrCreate([
            'created_by' => $user1->id,
            'title' => 'Team Chat',
            'is_group' => true,
        ]);

        if ($conversation2->users()->count() === 0) {
            $conversation2->users()->attach([$user1->id, $user2->id, $user3->id]);
        }

        if ($conversation2->messages()->count() === 0) {
            Message::create([
                'conversation_id' => $conversation2->id,
                'user_id' => $user1->id,
                'body' => 'Welcome to the team chat!',
                'type' => 'text',
            ]);
        }

        $this->command->info('Chat seeder completed!');
        $this->command->info('Test users created:');
        $this->command->info('- alice@example.com / password');
        $this->command->info('- bob@example.com / password');
        $this->command->info('- charlie@example.com / password');
    }
}

