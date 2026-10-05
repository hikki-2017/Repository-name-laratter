<?php

namespace Tests\Feature;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TweetTest extends TestCase
{
    use RefreshDatabase;

    // 一覧画面のテスト
    public function test_displays_tweets(): void
    {
        // ユーザを作成
        $user = User::factory()->create();

        // ユーザを認証
        $this->actingAs($user);

        // Tweetを作成
        $tweet = Tweet::factory()->create();

        // GETリクエスト
        $response = $this->get('/tweets');

        // レスポンスにTweetの内容と投稿者名が含まれていることを確認
        $response->assertStatus(200);
        $response->assertSee($tweet->tweet);
        $response->assertSee($tweet->user->name);
    }

    // 作成画面のテスト
    public function test_displays_the_create_tweet_page(): void
    {
        // テスト用のユーザーを作成
        $user = User::factory()->create();

        // ユーザーを認証（ログイン）
        $this->actingAs($user);

        // 作成画面にアクセス
        $response = $this->get('/tweets/create');

        // ステータスコードが200であることを確認
        $response->assertStatus(200);
    }

    // 作成処理のテスト
    public function test_allows_authenticated_users_to_create_a_tweet(): void
    {
        // ユーザを作成
        $user = User::factory()->create();

        // ユーザを認証
        $this->actingAs($user);

        // Tweetを作成
        $tweetData = ['tweet' => 'This is a test tweet.'];

        // POSTリクエスト
        $response = $this->post('/tweets', $tweetData);

        // データベースに保存されたことを確認
        $this->assertDatabaseHas('tweets', $tweetData);

        // レスポンスの確認
        $response->assertStatus(302);
        $response->assertRedirect('/tweets');
    }

    // 詳細画面のテスト
    public function test_displays_a_tweet(): void
    {
        // ユーザを作成
        $user = User::factory()->create();

        // ユーザを認証
        $this->actingAs($user);

        // Tweetを作成
        $tweet = Tweet::factory()->create();

        // GETリクエスト
        $response = $this->get("/tweets/{$tweet->id}");

        // レスポンスにTweetの内容・日時・投稿者名が含まれていることを確認
        $response->assertStatus(200);
        $response->assertSee($tweet->tweet);
        $response->assertSee($tweet->created_at->format('Y-m-d H:i'));
        $response->assertSee($tweet->updated_at->format('Y-m-d H:i'));
        $response->assertSee($tweet->user->name);
    }

    // 編集画面のテスト
    public function test_displays_the_edit_tweet_page(): void
    {
        // テスト用のユーザーを作成
        $user = User::factory()->create();

        // ユーザーを認証（ログイン）
        $this->actingAs($user);

        // Tweetを作成
        $tweet = Tweet::factory()->create(['user_id' => $user->id]);

        // 編集画面にアクセス
        $response = $this->get("/tweets/{$tweet->id}/edit");

        // ステータスコードが200であることを確認
        $response->assertStatus(200);

        // ビューにTweetの内容が含まれていることを確認
        $response->assertSee($tweet->tweet);
    }

    // 更新処理のテスト
    public function test_allows_a_user_to_update_their_tweet(): void
    {
        // ユーザを作成
        $user = User::factory()->create();

        // ユーザを認証
        $this->actingAs($user);

        // Tweetを作成
        $tweet = Tweet::factory()->create(['user_id' => $user->id]);

        // 更新データ
        $updatedData = ['tweet' => 'Updated tweet content.'];

        // PUTリクエスト
        $response = $this->put("/tweets/{$tweet->id}", $updatedData);

        // データベースが更新されたことを確認
        $this->assertDatabaseHas('tweets', $updatedData);

        // レスポンスの確認
        $response->assertStatus(302);
        $response->assertRedirect("/tweets/{$tweet->id}");
    }

    // 削除処理のテスト
    public function test_allows_a_user_to_delete_their_tweet(): void
    {
        // ユーザを作成
        $user = User::factory()->create();

        // ユーザを認証
        $this->actingAs($user);

        // Tweetを作成
        $tweet = Tweet::factory()->create(['user_id' => $user->id]);

        // DELETEリクエスト
        $response = $this->delete("/tweets/{$tweet->id}");

        // データベースから削除されたことを確認
        $this->assertDatabaseMissing('tweets', ['id' => $tweet->id]);

        // レスポンスの確認
        $response->assertStatus(302);
        $response->assertRedirect('/tweets');
    }

    // キーワードを含むツイートを検索できることを確認
    public function test_can_search_tweets_by_content_keyword(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // キーワードを含むツイートを作成
        Tweet::factory()->create([
            'tweet' => 'This is a test tweet',
            'user_id' => $user->id,
        ]);

        // キーワードを含まないツイートを作成
        Tweet::factory()->create([
            'tweet' => 'This is another tweet',
            'user_id' => $user->id,
        ]);

        // キーワード "test" で検索
        $response = $this->get(route('tweets.search', ['keyword' => 'test']));

        $response->assertStatus(200);
        $response->assertSee('This is a test tweet');
        $response->assertDontSee('This is another tweet');
    }

    // 一致するツイートがない場合のテスト
    public function test_shows_no_tweets_if_no_match_found(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Tweet::factory()->create([
            'tweet' => 'This is a tweet',
            'user_id' => $user->id,
        ]);

        // 存在しないキーワードで検索
        $response = $this->get(route('tweets.search', ['keyword' => 'nonexistent']));

        $response->assertStatus(200);
        $response->assertDontSee('This is a tweet');
        $response->assertSee('No tweets found.');
    }
}
