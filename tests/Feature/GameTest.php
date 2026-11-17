<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_play_without_scores_being_saved(): void
    {
        $this->get('/')->assertOk()->assertSee('Playing as guest');
        $this->postJson('/game/deal', ['bet' => 5])->assertOk()->assertJsonPath('credits', 95)->assertJsonCount(5, 'hand');
        $this->postJson('/game/draw', ['hold' => [0, 1]])->assertOk()->assertJsonStructure(['hand', 'result', 'payout', 'credits']);
        $this->assertDatabaseCount('scores', 0);
    }

    public function test_draw_requires_a_deal(): void
    {
        $this->postJson('/game/draw')->assertStatus(422);
    }

    public function test_logged_in_user_scores_are_recorded(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->postJson('/game/deal', ['bet' => 2])->assertOk();
        $draw = $this->postJson('/game/draw', ['hold' => []])->assertOk()->json();

        $this->assertDatabaseHas('scores', ['user_id' => $user->id, 'bet' => 2, 'credits_after' => $draw['credits']]);
        $this->assertSame($draw['credits'], $user->fresh()->credits);
        $this->get('/history')->assertOk();
        $this->get('/leaderboard')->assertOk()->assertSee($user->name);
    }

    public function test_register_and_login(): void
    {
        $this->post('/register', [
            'name' => 'ace', 'email' => 'ace@example.com',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertRedirect('/');
        $this->assertAuthenticated();

        $this->post('/logout');
        $this->assertGuest();

        $this->post('/login', ['email' => 'ace@example.com', 'password' => 'secret123'])->assertRedirect('/');
        $this->assertAuthenticated();
    }
}
