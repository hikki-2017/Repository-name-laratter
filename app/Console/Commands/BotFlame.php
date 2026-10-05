<?php

namespace App\Console\Commands;

use App\Models\Tweet;
use App\Models\User;
use App\Services\BotBrain;
use Illuminate\Console\Command;

/**
 * 炎上シミュレータ。
 *
 * 炎上の本質は「批判の文面」ではなく「伝播の仕組み」にある。
 * このコマンドは次の3段階を再現する。
 *
 *   第1波 … 同じコミュニティ内の反応（まだ内輪）
 *   第2波 … 第1波の参加者を「フォローしている人」が流入（飛び火）
 *   第3波 … 無関係な人まで参加（コミュニティの外へ拡散）
 *
 * 返信の文面は BotBrain が判定して選ぶ。
 * 誹謗中傷ではなく「意見の対立」の範囲にとどめている。
 */
class BotFlame extends Command
{
    protected $signature = 'bot:flame
                            {tweet? : 炎上させる投稿のID（省略時は対立を含む投稿を自動で選ぶ）}
                            {--waves=3 : 何波まで広げるか}
                            {--dry-run : 実際には書き込まず、誰が参加するかだけ表示する}';

    protected $description = '投稿を炎上させ、フォロー関係を伝って批判が拡散する様子を再現する';

    public function handle(BotBrain $brain): int
    {
        $tweet = $this->resolveTweet($brain);

        if (! $tweet) {
            $this->error('対象の投稿が見つかりませんでした。');

            return self::FAILURE;
        }

        $bots = User::where('email', 'like', 'bot%@laratter.test')->get();

        if ($bots->count() < 5) {
            $this->error('ボットが足りません。先に BotSeeder を実行してください。');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $waves = max(1, min(3, (int) $this->option('waves')));

        $this->newLine();
        $this->line('炎上対象: <fg=yellow>'.$tweet->tweet.'</>');
        $this->line('投稿者  : '.$tweet->user->name.'  (ID: '.$tweet->id.')');
        $this->line('話題判定: '.$brain->detectTopic($tweet).' / 対立を含む: '.($brain->isControversial($tweet) ? 'はい' : 'いいえ'));
        $this->newLine();

        $before = [
            'comments' => $tweet->comments()->count(),
            'likes' => $tweet->liked()->count(),
        ];

        // 投稿者本人は参加しない
        $candidates = $bots->where('id', '!=', $tweet->user_id);

        $joined = collect();      // これまでに参加した人
        $waveMembers = collect(); // 直前の波の参加者

        for ($wave = 1; $wave <= $waves; $wave++) {
            $members = $this->pickWave($wave, $candidates, $joined, $waveMembers, $tweet);

            if ($members->isEmpty()) {
                $this->line("第{$wave}波: 参加者なし（延焼が止まりました）");
                break;
            }

            $label = match ($wave) {
                1 => '第1波 内輪の反応',
                2 => '第2波 フォロー経由で飛び火',
                default => '第3波 無関係な層まで拡散',
            };

            $this->line("<fg=red>{$label}</> … {$members->count()}人");

            foreach ($members as $bot) {
                // 波が進むほど反論の割合が増える（これが「燃え広がる」感覚をつくる）
                $disagreeRate = [1 => 50, 2 => 70, 3 => 85][$wave] ?? 70;
                $mood = random_int(1, 100) <= $disagreeRate ? 'disagree' : 'agree';
                $text = $brain->replyTo($tweet, $bot, $mood);

                $mark = $mood === 'disagree' ? '<fg=red>×</>' : '<fg=green>○</>';
                $this->line("   {$mark} {$bot->name}: {$text}");

                if (! $dry) {
                    $tweet->comments()->create([
                        'comment' => $text,
                        'user_id' => $bot->id,
                    ]);

                    // 炎上中の投稿は、賛否問わず「いいね」も伸びる
                    if (random_int(1, 100) <= 40) {
                        $tweet->liked()->syncWithoutDetaching([$bot->id]);
                    }
                }
            }

            $joined = $joined->merge($members);
            $waveMembers = $members;
            $this->newLine();
        }

        if ($dry) {
            $this->warn('--dry-run のため、データベースには書き込んでいません。');

            return self::SUCCESS;
        }

        $tweet->refresh();
        $after = [
            'comments' => $tweet->comments()->count(),
            'likes' => $tweet->liked()->count(),
        ];

        $this->table(
            ['', '炎上前', '炎上後'],
            [
                ['コメント', $before['comments'], $after['comments']],
                ['いいね', $before['likes'], $after['likes']],
                ['参加者', 0, $joined->unique('id')->count()],
            ]
        );

        $this->info('この投稿を見る: http://localhost/tweets/'.$tweet->id);

        return self::SUCCESS;
    }

    /**
     * 炎上させる投稿を決める。
     */
    private function resolveTweet(BotBrain $brain): ?Tweet
    {
        if ($id = $this->argument('tweet')) {
            return Tweet::with('user')->find($id);
        }

        // 対立を含む投稿を優先して選ぶ
        $candidates = Tweet::with('user')->latest()->take(100)->get();
        $controversial = $candidates->filter(fn (Tweet $t) => $brain->isControversial($t));

        return $controversial->isNotEmpty()
            ? $controversial->random()
            : $candidates->first();
    }

    /**
     * その波に参加する人を選ぶ。
     *
     * 第1波 … 投稿者をフォローしている人（内輪）
     * 第2波 … 第1波の参加者をフォローしている人（飛び火）
     * 第3波 … それ以外の人（無関係な層）
     */
    private function pickWave(int $wave, $candidates, $joined, $waveMembers, Tweet $tweet)
    {
        $joinedIds = $joined->pluck('id');
        $rest = $candidates->whereNotIn('id', $joinedIds);

        if ($wave === 1) {
            // 投稿者のフォロワー = その投稿が最初に届く範囲
            $followerIds = $tweet->user->followers->pluck('id');
            $pool = $rest->whereIn('id', $followerIds);
        } elseif ($wave === 2) {
            // 直前の波の参加者をフォローしている人に伝わる
            $reachIds = collect();
            foreach ($waveMembers as $member) {
                $reachIds = $reachIds->merge($member->followers->pluck('id'));
            }
            $pool = $rest->whereIn('id', $reachIds->unique());
        } else {
            // もはや繋がりは関係なくなる
            $pool = $rest;
        }

        if ($pool->isEmpty()) {
            return collect();
        }

        // 波が進むほど参加者が増える
        $take = min($pool->count(), [1 => 3, 2 => 5, 3 => 8][$wave] ?? 5);

        return $pool->shuffle()->take($take)->values();
    }
}
