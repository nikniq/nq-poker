<?php

namespace App\Poker;

/**
 * Jacks-or-Better video poker evaluator.
 * Cards are strings like "AS", "TD", "9H" (rank + suit).
 */
class HandEvaluator
{
    public const RANKS = '23456789TJQKA';

    public const SUITS = 'SHDC';

    /** Payout multipliers per credit bet. */
    public const PAYTABLE = [
        'Royal Flush' => 250,
        'Straight Flush' => 50,
        'Four of a Kind' => 25,
        'Full House' => 9,
        'Flush' => 6,
        'Straight' => 4,
        'Three of a Kind' => 3,
        'Two Pair' => 2,
        'Jacks or Better' => 1,
    ];

    public static function newDeck(): array
    {
        $deck = [];
        foreach (str_split(self::SUITS) as $s) {
            foreach (str_split(self::RANKS) as $r) {
                $deck[] = $r.$s;
            }
        }
        // random_int-based Fisher–Yates shuffle
        for ($i = count($deck) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$deck[$i], $deck[$j]] = [$deck[$j], $deck[$i]];
        }

        return $deck;
    }

    /** @return string|null name of the winning hand, or null for no win */
    public static function evaluate(array $hand): ?string
    {
        $values = array_map(fn ($c) => strpos(self::RANKS, $c[0]) + 2, $hand);
        $suits = array_map(fn ($c) => $c[1], $hand);
        sort($values);

        $flush = count(array_unique($suits)) === 1;
        $unique = array_values(array_unique($values));
        $straight = count($unique) === 5 && ($values[4] - $values[0] === 4 || $values === [2, 3, 4, 5, 14]);

        $counts = array_count_values($values);
        arsort($counts);
        $groups = array_values($counts);

        if ($straight && $flush) {
            return $values[0] === 10 ? 'Royal Flush' : 'Straight Flush';
        }
        if ($groups[0] === 4) {
            return 'Four of a Kind';
        }
        if ($groups[0] === 3 && $groups[1] === 2) {
            return 'Full House';
        }
        if ($flush) {
            return 'Flush';
        }
        if ($straight) {
            return 'Straight';
        }
        if ($groups[0] === 3) {
            return 'Three of a Kind';
        }
        if ($groups[0] === 2 && $groups[1] === 2) {
            return 'Two Pair';
        }
        if ($groups[0] === 2 && array_key_first($counts) >= 11) {
            return 'Jacks or Better';
        }

        return null;
    }
}
