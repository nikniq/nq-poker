<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_have_menu_footer_and_link_back_to_nikniq(): void
    {
        config(['services.nikniq.url' => 'https://nikniq.test/']);
        foreach (['/', '/how-to-play', '/leaderboard', '/login', '/register'] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('class="menu"', false)
                ->assertSee('class="footer"', false)
                ->assertSee('href="https://nikniq.test"', false)
                ->assertSee('Back to');
        }
    }

    public function test_menu_shows_account_links_for_signed_in_player(): void
    {
        $user = User::factory()->create(['name' => 'Ace']);
        $user->forceFill(['nikniq_id' => '42'])->save();
        $this->actingAs($user)->get('/')->assertSee('Ace')->assertSee('My Scores')->assertSee('My nikniq.com account')->assertDontSee('Sign up free');
    }

    public function test_game_has_full_screen_control(): void
    {
        $this->get('/')->assertOk()->assertSee('id="fullscreen"', false)->assertSee('Full screen');
    }
}
