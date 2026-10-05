<?php

namespace App\Console\Commands;

use App\Models\Tweet;
use App\Models\User;
use App\Services\BotBrain;
use Database\Seeders\BotSeeder;
use Illuminate\Console\Command;

class BotActivity extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bot:activity
                            {--posts=5 : 新しく投稿する件数}
                            {--likes=15 : いいねする回数}
                            {--comments=5 : コメントする件数}';

    /**
     * The console command description.
     */
    protected $description = 'ボットに投稿・いいね・コメントをさせてタイムラインを動かす';

    public function __construct(private BotBrain $brain)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $bots = User::where('email', 'like', 'bot%@laratter.test')->get();

        if ($bots->isEmpty()) {
            $this->error('ボットがいません。先に次を実行してください:');
            $this->line('  ./vendor/bin/sail php artisan db:seed --class=BotSeeder');

            return self::FAILURE;
        }

        // メールアドレスの番号から所属分野を割り出す（1分野につき4体）
        $topicKeys = array_keys(BotSeeder::TOPICS);
        $topicOf = function (User $bot) use ($topicKeys): string {
            preg_match('/bot(\d+)@/', $bot->email, $m);
            $index = (int) ($m[1] ?? 1);

            return $topicKeys[intdiv($index - 1, 4) % count($topicKeys)];
        };

        // 1. 投稿
        $posted = 0;
        foreach ($bots->shuffle()->take((int) $this->option('posts')) as $bot) {
            $topic = $topicOf($bot);
            $posts = BotSeeder::TOPICS[$topic]['posts'];
            $bot->tweets()->create(['tweet' => $posts[array_rand($posts)]]);
            $posted++;
        }

        // 2. いいね（自分の分野の投稿を優先）
        $liked = 0;
        $recent = Tweet::latest()->take(60)->get();

        for ($i = 0; $i < (int) $this->option('likes'); $i++) {
            $bot = $bots->random();
            $tweet = $recent->where('user_id', '!=', $bot->id)->random();

            if ($tweet->liked()->where('users.id', $bot->id)->exists()) {
                continue;
            }

            $tweet->liked()->attach($bot->id);
            $liked++;
        }

        // 3. コメント（BotBrain が本文を読んで返信を決める）
        $commented = 0;
        for ($i = 0; $i < (int) $this->option('comments'); $i++) {
            $bot = $bots->random();
            $tweet = $recent->where('user_id', '!=', $bot->id)->random();

            $tweet->comments()->create([
                'comment' => $this->brain->replyTo($tweet, $bot),
                'user_id' => $bot->id,
            ]);
            $commented++;
        }

        $this->info("投稿 {$posted} 件 / いいね {$liked} 件 / コメント {$commented} 件を追加しました。");

        return self::SUCCESS;
    }
}
