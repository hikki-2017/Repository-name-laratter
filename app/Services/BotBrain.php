<?php

namespace App\Services;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ボットの「判定」を担当するサービス。
 *
 * 投稿の本文を読んで
 *   - どの話題か（topic）
 *   - 対立を含む意見か（controversial）
 * を判定し、それに合った返信を返す。
 *
 * config('bot.brain') が 'ollama' のときはローカルLLMに生成させ、
 * 失敗した場合はルールベースに自動で戻る。
 */
class BotBrain
{
    /** 話題を判定するためのキーワード */
    private const TOPIC_KEYWORDS = [
        'gourmet' => ['ラーメン', 'つけ麺', '麺', 'カレー', '牛丼', '定食', 'チャーハン', '餃子', 'スイーツ', '蕎麦', '味噌', '飯', '食'],
        'tech' => ['コード', 'テスト', 'リレーション', 'マイグレーション', 'Docker', '命名', 'レビュー', 'ログ', 'リファクタ', 'バグ', 'タブ', 'スペース', '設計'],
        'music' => ['レコード', 'ベース', 'ギター', 'ライブ', 'イヤホン', 'アルバム', '歌詞', 'メトロノーム', 'スピーカー', '曲', '音'],
        'sports' => ['走', 'ラン', 'スクワット', '試合', 'ストレッチ', 'シューズ', '体幹', '記録', '運動', '筋トレ', 'トレ'],
        'travel' => ['各駅', '温泉', '駅弁', '商店街', '空港', '荷物', '宿', 'バス', '路線', '旅', '町'],
    ];

    /** 対立を含む意見かを判定するためのキーワード */
    private const CONTROVERSIAL_KEYWORDS = [
        '派', '邪道', '正義', 'どっち', 'より', '一択', 'べき', 'は不要', 'いらない',
        '最強', '最悪', '間違って', '常識', '当たり前', '許せ', 'おかしい',
    ];

    /** 同意する返信（話題別） */
    private const AGREE = [
        'gourmet' => ['わかります', 'それ好きです', '今度行ってみます', '同じこと思ってました', 'お腹すいてきた'],
        'tech' => ['たしかに', '参考になります', '同じ経験あります', 'それ大事ですね', '自分もそうしてます'],
        'music' => ['いいですね', 'わかる人にはわかるやつ', '自分も同じです', '聴いてみます', 'その感覚好きです'],
        'sports' => ['えらい', '自分も見習います', 'わかります', '続けるの大事ですよね', 'いい習慣ですね'],
        'travel' => ['いいですね', '行ってみたい', 'わかります', '写真見たいです', 'その時間、贅沢ですね'],
        'other' => ['たしかに', 'わかります', 'いい話', '参考になります'],
    ];

    /** 質問を返す返信（話題別） */
    private const ASK = [
        'gourmet' => ['どのお店ですか？', 'おすすめの時間帯ありますか？', '並びました？'],
        'tech' => ['どう解決しました？', 'バージョンいくつですか？', '手順どこかにまとめてます？'],
        'music' => ['何を聴いてるんですか？', '機材は何使ってます？', 'どこのライブですか？'],
        'sports' => ['どれくらい続けてます？', 'ペースどのくらいですか？', '何か記録つけてます？'],
        'travel' => ['どのあたりですか？', '何泊されました？', '交通手段は？'],
        'other' => ['詳しく聞きたいです', 'どうやってるんですか？'],
    ];

    /** 対立意見に対する反論（穏当な範囲にとどめる） */
    private const DISAGREE = [
        'gourmet' => ['そこは意見が分かれそう', '自分は逆の派です', 'うーん、賛成しかねます', '好みの問題では？'],
        'tech' => ['ケースバイケースだと思います', 'そこは状況によりませんか', '自分は別の方針です', '一概には言えない気が'],
        'music' => ['そこは好みかなと', '自分は違う意見です', '一理ありますが…', '人によると思います'],
        'sports' => ['体質にもよりますよね', '自分には合いませんでした', 'そこは異論あります', '無理は禁物では'],
        'travel' => ['自分は逆派です', 'それは人によりそう', 'うーん、どうでしょう', '目的によりませんか'],
        'other' => ['そこは意見が分かれますね', '自分は違う考えです', '一概には言えないかと'],
    ];

    /**
     * 投稿に対する返信を1つ返す。
     *
     * @param  string  $mood  'auto' | 'agree' | 'ask' | 'disagree'
     */
    public function replyTo(Tweet $tweet, User $bot, string $mood = 'auto'): string
    {
        if (config('bot.brain') === 'ollama') {
            $generated = $this->askOllama($tweet, $mood);

            if ($generated !== null) {
                return $generated;
            }
            // 失敗したらルールベースに落ちる
        }

        return $this->decideByRules($tweet, $mood);
    }

    /**
     * 本文からどの話題かを判定する。
     */
    public function detectTopic(Tweet $tweet): string
    {
        $scores = [];

        foreach (self::TOPIC_KEYWORDS as $topic => $words) {
            $score = 0;
            foreach ($words as $word) {
                if (str_contains($tweet->tweet, $word)) {
                    $score++;
                }
            }
            $scores[$topic] = $score;
        }

        arsort($scores);
        $top = array_key_first($scores);

        return $scores[$top] > 0 ? $top : 'other';
    }

    /**
     * 対立を含む意見かどうかを判定する。
     */
    public function isControversial(Tweet $tweet): bool
    {
        foreach (self::CONTROVERSIAL_KEYWORDS as $word) {
            if (str_contains($tweet->tweet, $word)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ルールベースの判定。
     */
    private function decideByRules(Tweet $tweet, string $mood): string
    {
        $topic = $this->detectTopic($tweet);

        if ($mood === 'auto') {
            // 対立を含む投稿には反論が付きやすい
            $mood = $this->isControversial($tweet)
                ? (random_int(1, 100) <= 60 ? 'disagree' : 'agree')
                : (random_int(1, 100) <= 25 ? 'ask' : 'agree');
        }

        $pool = match ($mood) {
            'disagree' => self::DISAGREE[$topic] ?? self::DISAGREE['other'],
            'ask' => self::ASK[$topic] ?? self::ASK['other'],
            default => self::AGREE[$topic] ?? self::AGREE['other'],
        };

        return $pool[array_rand($pool)];
    }

    /**
     * ローカルLLM（Ollama）に返信を生成させる。
     * 失敗したら null を返してルールベースに委ねる。
     */
    private function askOllama(Tweet $tweet, string $mood): ?string
    {
        $stance = match ($mood) {
            'disagree' => '穏やかに異論を述べる',
            'ask' => '短く質問する',
            'agree' => '共感する',
            default => '自然に反応する',
        };

        $prompt = <<<PROMPT
        あなたは日本語のSNSユーザーです。次の投稿に返信してください。

        投稿: 「{$tweet->tweet}」

        条件:
        - {$stance}
        - 40文字以内
        - 攻撃的な言葉、侮辱、人格否定は使わない
        - 返信の本文だけを出力する（前置きや引用符は不要）
        PROMPT;

        try {
            $response = Http::timeout(config('bot.ollama.timeout'))
                ->post(config('bot.ollama.url').'/api/generate', [
                    'model' => config('bot.ollama.model'),
                    'prompt' => $prompt,
                    'stream' => false,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $text = trim((string) $response->json('response'));
            $text = trim($text, "「」\"'");

            if ($text === '') {
                return null;
            }

            return mb_substr($text, 0, 255);
        } catch (\Throwable $e) {
            Log::warning('BotBrain: Ollama への接続に失敗しました。ルールベースに切り替えます。', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
