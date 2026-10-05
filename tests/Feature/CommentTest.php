<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    // 作成画面のテスト
    public function test_displays_the_comment_creation_form(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tweet = Tweet::factory()->create(['user_id' => $user->id]);

        $response = $this->get(route('tweets.comments.create', $tweet));

        $response->assertStatus(200);
        $response->assertViewIs('tweets.comments.create');
        $response->assertViewHas('tweet', $tweet);
    }

    // 作成処理のテスト
    public function test_allows_authenticated_users_to_create_a_comment(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tweet = Tweet::factory()->create(['user_id' => $user->id]);
        $commentData = ['comment' => 'store test comment'];

        $response = $this->post(route('tweets.comments.store', $tweet), $commentData);

        $response->assertRedirect(route('tweets.show', $tweet));
        $this->assertDatabaseHas('comments', [
            'comment' => $commentData['comment'],
            'tweet_id' => $tweet->id,
            'user_id' => $user->id,
        ]);
    }

    // 詳細画面のテスト
    public function test_displays_a_comment(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tweet = Tweet::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create(['tweet_id' => $tweet->id, 'user_id' => $user->id]);

        $response = $this->get(route('tweets.comments.show', [$tweet, $comment]));

        $response->assertStatus(200);
        $response->assertViewIs('tweets.comments.show');
        $response->assertViewHas('tweet', $tweet);
        $response->assertViewHas('comment', $comment);
    }

    // 編集画面のテスト
    public function test_displays_the_edit_comment_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tweet = Tweet::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create(['tweet_id' => $tweet->id, 'user_id' => $user->id]);

        $response = $this->get(route('tweets.comments.edit', [$tweet, $comment]));

        $response->assertStatus(200);
        $response->assertViewIs('tweets.comments.edit');
        $response->assertViewHas('tweet', $tweet);
        $response->assertViewHas('comment', $comment);
    }

    // 更新処理のテスト
    public function test_allows_a_user_to_update_their_comment(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tweet = Tweet::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create(['tweet_id' => $tweet->id, 'user_id' => $user->id]);
        $updatedData = ['comment' => 'update test comment'];

        $response = $this->put(route('tweets.comments.update', [$tweet, $comment]), $updatedData);

        $response->assertRedirect(route('tweets.comments.show', [$tweet, $comment]));
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'comment' => $updatedData['comment'],
        ]);
    }

    // 削除処理のテスト
    public function test_allows_a_user_to_delete_their_comment(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tweet = Tweet::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create(['tweet_id' => $tweet->id, 'user_id' => $user->id]);

        $response = $this->delete(route('tweets.comments.destroy', [$tweet, $comment]));

        $response->assertRedirect(route('tweets.show', $tweet));
        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }
}
