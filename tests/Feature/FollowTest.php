<?php

namespace Tests\Feature;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowTest extends TestCase
{
    use RefreshDatabase;

    // ユーザをフォローできることを確認
    public function test_can_follow_a_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $followedUser = User::factory()->create();
        $this->post(route('follow.store', $followedUser));

        $this->assertDatabaseHas('follows', [
            'follow_id' => $user->id,
            'follower_id' => $followedUser->id,
        ]);
    }

    // ユーザをアンフォローできることを確認
    public function test_can_unfollow_a_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $followedUser = User::factory()->create();
        $user->follows()->attach($followedUser);
        $this->delete(route('follow.destroy', $followedUser));

        $this->assertDatabaseMissing('follows', [
            'follow_id' => $user->id,
            'follower_id' => $followedUser->id,
        ]);
    }

    // 自分のユーザページに自分とフォローユーザの Tweet が表示されることを確認
    public function test_displays_the_user_and_followings_tweet_at_current_user_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $followedUser = User::factory()->create();
        $user->follows()->attach($followedUser);

        $otherUser = User::factory()->create();

        $userTweet = Tweet::factory()->create(['user_id' => $user->id]);
        $followedTweet = Tweet::factory()->create(['user_id' => $followedUser->id]);
        $otherTweet = Tweet::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->get(route('profile.show', $user));

        $response->assertStatus(200);
        $response->assertSee($userTweet->tweet);      // 自分の Tweet
        $response->assertSee($followedTweet->tweet);  // フォローしているユーザの Tweet
        $response->assertSee($followedUser->name);
        $response->assertDontSee($otherTweet->tweet); // フォローしていないユーザの Tweet は表示されない
    }

    // 他のユーザのユーザページにそのユーザの Tweet だけが表示されることを確認
    public function test_displays_the_another_user_tweet_at_user_show_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $anotherUser = User::factory()->create();

        $userTweet = Tweet::factory()->create(['user_id' => $user->id]);
        $anotherTweet = Tweet::factory()->create(['user_id' => $anotherUser->id]);

        $response = $this->get(route('profile.show', $anotherUser));

        $response->assertStatus(200);
        $response->assertSee($anotherTweet->tweet);  // そのユーザの Tweet
        $response->assertSee($anotherUser->name);
        $response->assertDontSee($userTweet->tweet); // 自分の Tweet は表示されない
    }
}
