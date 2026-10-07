# Laratter（機能追加版）

受講番号：6th-13
氏名：小河 輝生

## 1. プロダクトの紹介

講義で作成した SNS アプリケーション「Laratter」に，**講義にない独自機能として「ボット（自動で動くユーザー）」機能**を追加しました．

### 独自に追加した機能：ボット機能（講義外）

| コマンド / クラス | 内容 |
| --- | --- |
| `php artisan bot:activity` | ボットが自動で投稿・いいね・コメントをして，タイムラインを動かす（`--posts` `--likes` `--comments` で件数指定） |
| `php artisan bot:opinion` | 「ラーメンはつけ麺派」のような，意見が分かれる投稿をボットにさせる（`--count` で件数指定） |
| `php artisan bot:flame` | ある投稿が炎上し，**フォローのつながりをたどって批判が波のように広がる様子**を再現する．炎上前後のコメント数・いいね数・参加者数を表で表示（`--waves` で広がる段階数，`--dry-run` で書き込まずに確認） |
| `BotBrain`（`app/Services/BotBrain.php`） | 投稿の文章を読み，話題（食べ物・技術・音楽など）と「意見が割れる内容か」を判定して，合う返信を選ぶ．`.env` で `BOT_BRAIN=ollama` にするとローカル LLM（Ollama）に返信を生成させることもできる |

ボットのユーザーとフォロー関係は `database/seeders/BotSeeder.php` で作成します．

**作った理由**：SNS は「人が少ないと何も起きない」ので，一人で開発していても賑わいを再現できるようにしたかった．また，講義で学んだフォロー（多対多）のつながりを使って，「炎上がどう広がるか」を目で見て確かめられるようにしたかった．

### 講義の手順どおりに作成した機能（第06〜11章）

- Tweet の投稿・一覧・詳細・編集・削除
- いいね / いいね取り消し（画面上の表示は like / dislike）
- コメント（1対多）
- フォロー・アンフォロー（多対多）
- キーワード検索・マイページのタイムライン

## 2. こだわった点・苦労した点

- 炎上再現では，フォロー関係をたどって「誰が次の波で参加するか」を決める処理を工夫した（フォロワーのフォロワーへと段階的に広がる）
- BotBrain は，ルールベースとローカル LLM の両方で動くようにし，LLM が使えない環境でも動作するようにした
- フォロー機能では，ユーザー同士の多対多リレーションのため，中間テーブルの設計に苦労した
- 検索機能では，ルーティングの順番（`tweets/search` を `tweets/{tweet}` より前に書く）を間違えて 404 エラーになり，原因を調べて解決した
- コメント・フォロー・検索それぞれにテストコードを書き，全テストが通る状態にした

## 3. 操作方法

1. トップページから「Register」でアカウントを作成してログインする
2. 「Tweet作成」から Tweet を投稿する
3. Tweet 一覧から任意の Tweet をクリックして詳細画面を開く
4. 「コメントする」をクリックしてコメントを投稿する
5. サイドバーの「マイページ」をクリックして自分のページを開く
6. 別のユーザーのページを開き，「follow」ボタンを押してフォローする
7. マイページに戻ると，フォローしたユーザーの Tweet も表示される
8. サイドバーの「Tweet検索」からキーワードで Tweet を検索する

### ボット機能の動かし方

```bash
php artisan db:seed --class=BotSeeder   # ボットユーザーとフォロー関係を作成
php artisan bot:opinion --count=3       # 意見が割れる投稿をさせる
php artisan bot:activity                # 投稿・いいね・コメントでタイムラインを動かす
php artisan bot:flame                   # 炎上させて，フォロー経由で広がる様子を再現
```

（Sail 環境の場合は `./vendor/bin/sail php artisan ...`）
実行後に Tweet 一覧・検索画面を開くと，ボットの投稿や反応が表示されます．

テスト用アカウント：

| メールアドレス | パスワード |
| --- | --- |
| test@example.com | password |

## 4. 該当ファイル

| 機能 | 主なファイル |
| --- | --- |
| **ボット機能（独自）** | `app/Console/Commands/BotActivity.php` / `app/Console/Commands/BotOpinion.php` / `app/Console/Commands/BotFlame.php` / `app/Services/BotBrain.php` / `config/bot.php` / `database/seeders/BotSeeder.php` |
| コメント（講義） | `app/Models/Comment.php` / `app/Http/Controllers/CommentController.php` / `resources/views/tweets/comments/` |
| フォロー（講義） | `app/Http/Controllers/FollowController.php` / `app/Http/Controllers/ProfileController.php` / `resources/views/profile/show.blade.php` |
| 検索・タイムライン（講義） | `app/Models/Tweet.php`（`scopeKeyword` / `scopeTimeline`）/ `resources/views/tweets/search.blade.php` |

ルーティングは `routes/web.php`，サイドバーの「マイページ」「Tweet検索」は `resources/views/components/layouts/app/sidebar.blade.php` に追加．

## 5. リポジトリ

https://github.com/hikki-2017/Repository-name-laratter

## 6. 提出形式

**画面収録（必須）**

- 動画ファイル名：`6th-13_laratter_demo.mp4`
- 上記「3. 操作方法」の手順 1〜8 を順番に操作している様子を収録
