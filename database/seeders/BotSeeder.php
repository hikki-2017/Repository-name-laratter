<?php

namespace Database\Seeders;

use App\Models\Tweet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BotSeeder extends Seeder
{
    /**
     * 興味分野ごとのボット定義。
     * 同じ分野のボット同士は濃くフォローし合い、
     * 別分野とは薄くつながることでコミュニティ（クラスタ）が生まれる。
     */
    public const TOPICS = [
        'gourmet' => [
            'label' => 'グルメ',
            'members' => ['ラーメン太郎', '麺スキ', '食べ歩き子', '深夜の飯テロ'],
            'posts' => [
                '今日のラーメンは煮干しが効いてて最高だった',
                'つけ麺の並盛りでも十分多い。特盛は罠',
                '新しくできたカレー屋、スパイスの香りがすごい',
                '深夜に食べる牛丼、なぜあんなに美味しいのか',
                '駅前の定食屋が閉店してた。悲しい',
                'チャーハンのパラパラ感を家で再現したい',
                '餃子は焼きよりも水派です',
                'コンビニのスイーツ、進化しすぎでは',
                '朝から蕎麦を食べる休日が好き',
                '味噌ラーメンにバターを入れるのは邪道か正義か',
            ],
        ],
        'tech' => [
            'label' => '技術',
            'members' => ['コード書き', 'サーバ番人', 'バグ退治', '設計おじさん'],
            'posts' => [
                'リレーションを整理したらコードが半分になった',
                'テストが通ると気持ちがいい',
                'N+1問題、気づかないうちにやりがち',
                'マイグレーションのロールバックは慎重に',
                'Dockerが起動しないときは大体キャッシュ',
                '命名規則に従うと設定が減るのは体験がいい',
                'コードレビューで学ぶことが一番多い',
                'ログを読む習慣をつけたい',
                '動いているコードには理由がある',
                'リファクタリングは小さく刻むのがコツ',
            ],
        ],
        'music' => [
            'label' => '音楽',
            'members' => ['夜型ギター', 'ベース低音', '鍵盤さん', 'レコード堀り'],
            'posts' => [
                '中古レコード屋で掘り出し物を見つけた',
                'ベースラインが良い曲は何度でも聴ける',
                '練習後の疲労感が心地いい',
                'ライブ前の緊張感は何年経っても慣れない',
                'イヤホンを変えたら聴こえ方が変わった',
                '雨の日に合うアルバムを探している',
                '歌詞を読み込むと印象が変わる曲がある',
                'メトロノームと友達になれない',
                '路上ライブを久しぶりに見た。良かった',
                '古いスピーカーの音が好きだ',
            ],
        ],
        'sports' => [
            'label' => 'スポーツ',
            'members' => ['朝ラン', '筋トレ民', '観戦勢', 'ヨガ日和'],
            'posts' => [
                '今朝は5キロ走った。空気が気持ちいい',
                'スクワットの翌日は階段がつらい',
                '試合の終盤の逆転、鳥肌が立った',
                'ストレッチを習慣にしたら肩こりが減った',
                'ランニングシューズを新調した',
                '休息日をちゃんと取るのも練習のうち',
                '体幹を鍛えると姿勢が変わる',
                '雨で走れない日はどうしてる？',
                '記録が伸びない時期こそ続けるのが大事',
                '運動後のストレッチを忘れがち',
            ],
        ],
        'travel' => [
            'label' => '旅行',
            'members' => ['各駅停車', '温泉めぐり', '地図読み', '朝一の便'],
            'posts' => [
                '各駅停車でのんびり行くのが好き',
                '温泉で何も考えない時間が贅沢',
                '駅弁を買う瞬間が一番楽しい',
                '地方の商店街を歩くと発見がある',
                '早朝の空港は独特の雰囲気がある',
                '荷物は少ないほど旅は身軽になる',
                '宿の朝ごはんが決め手になることがある',
                '知らない町でバスに乗るのは少し緊張する',
                '海沿いの路線は景色がいい',
                '手帳に旅の予定を書き込む時間が好き',
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $bots = [];

        // 1. ボットユーザを作成
        $index = 0;
        foreach (self::TOPICS as $key => $topic) {
            foreach ($topic['members'] as $name) {
                $index++;
                $user = User::firstOrCreate(
                    ['email' => sprintf('bot%02d@laratter.test', $index)],
                    [
                        'name' => $name,
                        'password' => Hash::make('password'),
                        'email_verified_at' => $now,
                    ]
                );
                $bots[$key][] = $user;
            }
        }

        // 2. 投稿を作成（同じ分野の話題を投稿する）
        $tweets = [];
        foreach ($bots as $key => $members) {
            foreach ($members as $bot) {
                // 1体につき3〜6件
                $count = random_int(3, 6);
                $posts = collect(self::TOPICS[$key]['posts'])->shuffle()->take($count);

                foreach ($posts as $text) {
                    $created = $now->copy()->subMinutes(random_int(1, 60 * 24 * 3));

                    // 投稿時刻をばらけさせるため、タイムスタンプの自動更新を止めて保存する
                    $tweet = Tweet::withoutTimestamps(function () use ($bot, $text, $created) {
                        return $bot->tweets()->forceCreate([
                            'tweet' => $text,
                            'created_at' => $created,
                            'updated_at' => $created,
                        ]);
                    });

                    $tweets[$key][] = $tweet;
                }
            }
        }

        // 3. フォロー関係を作る（同分野は濃く、別分野は薄く）
        foreach ($bots as $key => $members) {
            foreach ($members as $bot) {
                // 同じ分野のボットは高確率で相互フォロー
                foreach ($members as $other) {
                    if ($bot->is($other)) {
                        continue;
                    }
                    if (random_int(1, 100) <= 80) {
                        $bot->follows()->syncWithoutDetaching([$other->id]);
                    }
                }

                // 別分野のボットは低確率でフォロー（クラスタ同士をゆるく繋ぐ）
                foreach ($bots as $otherKey => $otherMembers) {
                    if ($otherKey === $key) {
                        continue;
                    }
                    foreach ($otherMembers as $other) {
                        if (random_int(1, 100) <= 10) {
                            $bot->follows()->syncWithoutDetaching([$other->id]);
                        }
                    }
                }
            }
        }

        // 4. いいねとコメント（自分の分野の投稿に多く反応する）
        $reactions = [
            'わかる', 'それいいですね', '参考になります', '自分も同じでした',
            '今度試してみます', 'たしかに', 'いい話',
        ];

        foreach ($bots as $key => $members) {
            foreach ($members as $bot) {
                // 同分野の投稿にいいね
                foreach ($tweets[$key] as $tweet) {
                    if ($tweet->user_id === $bot->id) {
                        continue;
                    }
                    if (random_int(1, 100) <= 45) {
                        $tweet->liked()->syncWithoutDetaching([$bot->id]);
                    }
                    if (random_int(1, 100) <= 12) {
                        $tweet->comments()->create([
                            'comment' => $reactions[array_rand($reactions)],
                            'user_id' => $bot->id,
                        ]);
                    }
                }

                // 別分野の投稿にもたまにいいね
                foreach ($tweets as $otherKey => $otherTweets) {
                    if ($otherKey === $key) {
                        continue;
                    }
                    foreach ($otherTweets as $tweet) {
                        if (random_int(1, 100) <= 5) {
                            $tweet->liked()->syncWithoutDetaching([$bot->id]);
                        }
                    }
                }
            }
        }
    }
}
