<?php

namespace Tests\Unit;

use App\Poker\HandEvaluator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HandEvaluatorTest extends TestCase
{
    public static function hands(): array
    {
        return [
            [['AS', 'KS', 'QS', 'JS', 'TS'], 'Royal Flush'],
            [['9H', 'KH', 'QH', 'JH', 'TH'], 'Straight Flush'],
            [['9H', '9D', '9S', '9C', 'TH'], 'Four of a Kind'],
            [['9H', '9D', '9S', 'TC', 'TH'], 'Full House'],
            [['2H', '7H', '9H', 'JH', 'KH'], 'Flush'],
            [['AH', '2D', '3S', '4C', '5H'], 'Straight'],
            [['9H', '9D', '9S', '2C', 'TH'], 'Three of a Kind'],
            [['9H', '9D', '2S', '2C', 'TH'], 'Two Pair'],
            [['JH', 'JD', '2S', '3C', 'TH'], 'Jacks or Better'],
            [['TH', 'TD', '2S', '3C', '7H'], null],
            [['2H', '5D', '8S', 'JC', 'KH'], null],
        ];
    }

    #[DataProvider('hands')]
    public function test_evaluate(array $hand, ?string $expected): void
    {
        $this->assertSame($expected, HandEvaluator::evaluate($hand));
    }

    public function test_deck_has_52_unique_cards(): void
    {
        $this->assertCount(52, array_unique(HandEvaluator::newDeck()));
    }
}
