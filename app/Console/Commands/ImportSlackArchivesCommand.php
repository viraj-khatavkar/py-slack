<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use App\Support\EmojiMap;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportSlackArchivesCommand extends Command
{
    protected $signature = 'slack:import-archives {--users} {--channels} {--messages} {--map-threads} {--pins}';

    protected $description = 'Import Slack archive data from storage/app/slack-archive/';

    /** @var array<string, string> */
    private array $userMap = [];

    /** @var array<string, int> */
    private array $userIdMap = [];

    /** @var array<string, string|null> */
    private array $userImageMap = [];

    public function handle(): void
    {
        if ($this->option('users')) {
            $this->info('Importing Users...');
            $this->importUsers();
        }

        if ($this->option('channels')) {
            $this->info('Importing Channels...');
            $this->importChannels();
        }

        if ($this->option('messages')) {
            $this->info('Importing Messages...');
            $this->buildUserMaps();

            DB::table('messages')->truncate();

            $channels = Channel::all();

            foreach ($channels as $channel) {
                $this->newLine();
                $this->info('Importing Messages for '.$channel->name);
                $listOfFiles = Storage::files('slack-archive/'.$channel->name);

                $bar = $this->output->createProgressBar(count($listOfFiles));
                $bar->start();

                foreach ($listOfFiles as $file) {
                    if (Str::endsWith($file, '.json')) {
                        $this->importMessages($file, $channel);
                    }
                    $bar->advance();
                }

                $bar->finish();
            }

            $this->newLine();
            $this->info('Updating channel message counts...');
            DB::statement('UPDATE channels SET message_count = (SELECT COUNT(*) FROM messages WHERE messages.channel_id = channels.id)');
            Cache::forget('channels');
        }

        if ($this->option('map-threads')) {
            $this->info('Mapping Threads...');
            $this->mapThreads();
        }

        if ($this->option('pins')) {
            $this->info('Importing Pins...');
            $this->importPins();
        }
    }

    protected function buildUserMaps(): void
    {
        $this->userMap = User::pluck('name', 'slack_user_id')->all();
        $this->userIdMap = User::pluck('id', 'slack_user_id')->all();
        $this->userImageMap = User::pluck('image_url', 'slack_user_id')->all();
    }

    protected function importMessages(string $fileName, Channel $channel): void
    {
        $contents = File::get(Storage::path($fileName));
        $messages = json_decode($contents, associative: true);
        $now = now()->format('Y-m-d H:i:s');
        $batch = [];

        foreach ($messages as $message) {
            if (! isset($message['type']) || $message['type'] !== 'message') {
                continue;
            }

            if (isset($message['subtype']) && in_array($message['subtype'], ['bot_message', 'channel_join', 'tombstone'])) {
                continue;
            }

            if (! isset($message['user'])) {
                continue;
            }

            $userId = $this->userIdMap[$message['user']] ?? null;

            if (! $userId) {
                continue;
            }

            $text = $message['text'];

            if (stripos($text, '<http') !== false) {
                $linksInMessage = explode('<http', $text);
                foreach ($linksInMessage as $linkInMessage) {
                    $array = explode('>', $linkInMessage);
                    $linkTotalInBrackets = $array[0];
                    $array = explode('|', $array[0]);
                    $linkInMessage = $array[0];
                    $text = str_replace(
                        '<http'.$linkTotalInBrackets.'>',
                        '<a href="http'.$linkInMessage.'" target="_blank">http'.$linkInMessage.'</a>',
                        $text
                    );
                }
            }

            while (str_contains($text, '<@')) {
                $str = Str::of($text)->betweenFirst('<@', '>');
                $atUserName = $this->userMap[$str->value()] ?? null;

                $text = is_null($atUserName)
                    ? str_replace('<@'.$str.'>', '<strong>@Unknown-User</strong>', $text)
                    : str_replace('<@'.$str.'>', '<strong>@'.$atUserName.'</strong>', $text);
            }

            $text = $this->convertEmojisInText($text);

            $slackMessageTime = Carbon::createFromTimestamp($message['ts'])->shiftTimezone('UTC')
                ->setTimezone('Asia/Kolkata')
                ->format('Y-m-d H:i:s');

            $reactions = $this->resolveReactions($message['reactions'] ?? []);
            $replyUsers = $this->resolveReplyUsers($message['reply_users'] ?? []);

            $batch[] = [
                'channel_id' => $channel->id,
                'user_id' => $userId,
                'content' => $text,
                'ts' => $message['ts'],
                'thread_ts' => $message['thread_ts'] ?? null,
                'slack_timestamp' => $slackMessageTime,
                'reactions' => ! empty($reactions) ? json_encode($reactions) : null,
                'is_edited' => isset($message['edited']),
                'has_files' => ! empty($message['files']),
                'reply_users' => ! empty($replyUsers) ? json_encode($replyUsers) : null,
                'reply_users_count' => $message['reply_users_count'] ?? 0,
                'is_pinned' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 500) {
                DB::table('messages')->insert($batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            DB::table('messages')->insert($batch);
        }
    }

    protected function convertEmojisInText(string $text): string
    {
        return preg_replace_callback('/:([a-zA-Z0-9_+\-]+(?:::skin-tone-\d)?):/', function ($matches) {
            $unicode = EmojiMap::toUnicode($matches[1]);

            return $unicode ?? $matches[0];
        }, $text);
    }

    /**
     * @param  array<int, array{name: string, users: string[], count: int}>  $reactions
     * @return array<int, array{name: string, users: string[], count: int}>
     */
    protected function resolveReactions(array $reactions): array
    {
        return array_map(function (array $reaction) {
            $reaction['users'] = array_map(
                fn (string $slackId) => $this->userMap[$slackId] ?? 'Unknown',
                $reaction['users']
            );

            return $reaction;
        }, $reactions);
    }

    /**
     * @param  array<int, string>  $replyUserIds
     * @return array<int, array{name: string, image_url: string|null}>
     */
    protected function resolveReplyUsers(array $replyUserIds): array
    {
        $resolved = [];

        foreach ($replyUserIds as $slackId) {
            $name = $this->userMap[$slackId] ?? null;
            if ($name) {
                $resolved[] = [
                    'name' => $name,
                    'image_url' => $this->userImageMap[$slackId] ?? null,
                ];
            }
        }

        return $resolved;
    }

    protected function mapThreads(): void
    {
        $this->newLine();

        $updated = DB::update('
            UPDATE messages
            SET parent_id = (
                SELECT p.id
                FROM messages p
                WHERE p.ts = messages.thread_ts
                  AND p.channel_id = messages.channel_id
                  AND p.thread_ts = p.ts
                LIMIT 1
            )
            WHERE messages.thread_ts IS NOT NULL
              AND messages.thread_ts != messages.ts
              AND messages.parent_id IS NULL
        ');

        $this->info("Mapped {$updated} messages to their parent threads.");
    }

    protected function importUsers(): void
    {
        DB::table('users')->truncate();
        $contents = File::get(Storage::path('slack-archive/users.json'));
        $users = json_decode($contents, associative: true);

        $bar = $this->output->createProgressBar(count($users));
        $bar->start();

        $batch = [];

        foreach ($users as $user) {
            $batch[] = [
                'name' => empty($user['profile']['display_name']) ? $user['profile']['real_name'] : $user['profile']['display_name'],
                'image_url' => $user['profile']['image_72'],
                'slack_user_id' => $user['id'],
                'timezone' => $user['tz'] ?? null,
                'timezone_label' => $user['tz_label'] ?? null,
                'is_admin' => $user['is_admin'] ?? false,
                'is_bot' => $user['is_bot'] ?? false,
                'is_deleted' => $user['deleted'] ?? false,
                'title' => ! empty($user['profile']['title']) ? $user['profile']['title'] : null,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ];

            if (count($batch) >= 500) {
                DB::table('users')->insert($batch);
                $bar->advance(count($batch));
                $batch = [];
            }
        }

        if (! empty($batch)) {
            DB::table('users')->insert($batch);
            $bar->advance(count($batch));
        }

        $bar->finish();
    }

    protected function importChannels(): void
    {
        DB::table('channels')->truncate();
        $contents = File::get(Storage::path('slack-archive/channels.json'));
        $channels = json_decode($contents, associative: true);

        $userIdMap = User::pluck('id', 'slack_user_id')->all();

        $bar = $this->output->createProgressBar(count($channels));
        $bar->start();

        foreach ($channels as $channel) {
            Channel::create([
                'name' => $channel['name'],
                'purpose' => $channel['purpose']['value'] ?? null,
                'topic' => $channel['topic']['value'] ?? null,
                'creator_id' => $userIdMap[$channel['creator']] ?? null,
                'member_count' => count($channel['members'] ?? []),
                'created_at_slack' => isset($channel['created']) ? Carbon::createFromTimestamp($channel['created']) : null,
            ]);
            $bar->advance();
        }

        $bar->finish();
        Cache::forget('channels');
    }

    protected function importPins(): void
    {
        $contents = File::get(Storage::path('slack-archive/channels.json'));
        $channels = json_decode($contents, associative: true);

        $pinCount = 0;

        foreach ($channels as $channelData) {
            if (empty($channelData['pins'])) {
                continue;
            }

            $channel = Channel::where('name', $channelData['name'])->first();
            if (! $channel) {
                continue;
            }

            foreach ($channelData['pins'] as $pin) {
                $updated = Message::where('channel_id', $channel->id)
                    ->where('ts', $pin['id'])
                    ->update(['is_pinned' => true]);

                $pinCount += $updated;
            }
        }

        $this->info("Marked {$pinCount} messages as pinned.");
    }
}
