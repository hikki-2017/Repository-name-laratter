# Laratter（機能追加版）

受講番号：6th-13
氏名：小河 輝生

## 1. プロダクトの紹介

講義で作成した SNS アプリケーション「Laratter」に，以下の機能を追加しました．

- Tweet にコメントを投稿できる（1対多のリレーション）
- ユーザーをフォロー・アンフォローできる（多対多のリレーション）
- キーワードで Tweet を検索できる（スコープ・ページネーション）
- 自分のページでは，自分とフォローしているユーザーの Tweet がまとめて表示される

これらの機能を選んだ理由は，講義で学んだ「1対多」「多対多」のリレーションを実際に手を動かして実装することで，理解を深めたかったからです．

## 2. こだわった点・苦労した点

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

テスト用アカウント：

| メールアドレス | パスワード |
| --- | --- |
| test@example.com | password |

## 4. 追加機能と該当ファイル

| 追加機能 | 主なファイル |
| --- | --- |
| コメント投稿（1対多） | `app/Models/Comment.php` / `app/Http/Controllers/CommentController.php` / `database/migrations/2026_09_11_000000_create_comments_table.php` / `resources/views/tweets/comments/` |
| フォロー・アンフォロー（多対多） | `app/Http/Controllers/FollowController.php` / `app/Http/Controllers/ProfileController.php` / `database/migrations/2026_09_25_000000_create_follows_table.php` / `resources/views/profile/show.blade.php` |
| キーワード検索 | `app/Models/Tweet.php`（`scopeKeyword`）/ `TweetController@search` / `resources/views/tweets/search.blade.php` |
| マイページのタイムライン | `app/Models/Tweet.php`（`scopeTimeline`） |
| テスト | `tests/Feature/CommentTest.php` / `tests/Feature/FollowTest.php` / `tests/Feature/TweetTest.php` |

ルーティングは `routes/web.php`，サイドバーの「マイページ」「Tweet検索」は `resources/views/components/layouts/app/sidebar.blade.php` に追加．

## 5. リポジトリ

https://github.com/hikki-2017/Repository-name-laratter

## 6. 提出形式

**画面収録（必須）**

- 動画ファイル名：`6th-13_laratter_demo.mp4`
- 上記「3. 操作方法」の手順 1〜8 を順番に操作している様子を収録
