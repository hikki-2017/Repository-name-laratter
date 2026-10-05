<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * 炎上の「種」になる、意見が割れる投稿をボットにさせる。
 * 誹謗中傷ではなく、好みの対立（きのこ/たけのこ的なもの）に限定している。
 */
class BotOpinion extends Command
{
    protected $signature = 'bot:opinion {--count=3 : 投稿する件数}';

    protected $description = '意見が割れる投稿をボットにさせる（炎上の種をまく）';

    /** 対立を生みやすい投稿 */
    private const OPINIONS = [
        '正直、つけ麺よりラーメンの方が上だと思う',
        'ラーメンにバターを入れるのは邪道です',
        '餃子にタレは不要。そのままが一番',
        'コードのインデントはタブ一択でしょう',
        'コメントを書かないコードは読めなくて当たり前',
        'テストを書かない開発はありえないと思う',
        'イヤホンで音楽を語るのは無理があると思う',
        'ライブはやっぱり最前列じゃないと意味がない',
        '朝に走らない人は損してると思う',
        'ストレッチだけで運動した気になるのはどうなんだろう',
        '旅行は計画を立てないのが一番だと思う',
        '温泉で体を洗わずに入るのは常識的におかしい',
    ];

    public function handle(): int
    {
        $bots = User::where('email', 'like', 'bot%@laratter.test')->get();

        if ($bots->isEmpty()) {
            $this->error('ボットがいません。先に BotSeeder を実行してください。');

            return self::FAILURE;
        }

        $count = max(1, (int) $this->option('count'));
        $opinions = collect(self::OPINIONS)->shuffle()->take($count);

        foreach ($opinions as $text) {
            $bot = $bots->random();
            $tweet = $bot->tweets()->create(['tweet' => $text]);

            $this->line("  #{$tweet->id}  {$bot->name}: {$text}");
        }

        $this->newLine();
        $this->info('炎上させるには次を実行してください:');
        $this->line('  ./vendor/bin/sail php artisan bot:flame');

        return self::SUCCESS;
    }
}
