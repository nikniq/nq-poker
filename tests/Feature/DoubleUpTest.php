<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoubleUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_double_needs_a_pending_win(): void
    {
        $this->postJson('/game/double', ['pick' => 'red'])->assertStatus(422);
    }

    public function test_double_settles_credits_either_way(): void
    {
        $this->withSession(['guest_credits' => 50, 'double' => ['amount' => 10, 'round' => 0]]);
        $r = $this->postJson('/game/double', ['pick' => 'red'])->assertOk()->json();

        $red = str_contains('HD', $r['card'][1]);
        $this->assertSame($red, $r['won']);
        $this->assertSame($red ? 60 : 40, $r['credits']);
        $this->assertSame($red ? 20 : 0, $r['amount']);
        $this->assertSame($red, $r['can_double']);
    }

    public function test_double_is_capped_and_cleared_by_collect_or_deal(): void
    {
        $this->withSession(['guest_credits' => 50, 'double' => ['amount' => 10, 'round' => 4]]);
        $r = $this->postJson('/game/double', ['pick' => 'black'])->assertOk()->json();
        $this->assertFalse($r['can_double']);
        $this->postJson('/game/double', ['pick' => 'black'])->assertStatus(422);

        $this->withSession(['double' => ['amount' => 10, 'round' => 0]]);
        $this->postJson('/game/collect')->assertOk();
        $this->postJson('/game/double', ['pick' => 'red'])->assertStatus(422);

        $this->withSession(['double' => ['amount' => 10, 'round' => 0]]);
        $this->postJson('/game/deal', ['bet' => 1])->assertOk();
        $this->postJson('/game/double', ['pick' => 'red'])->assertStatus(422);
    }
}
