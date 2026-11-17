<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Poker\HandEvaluator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Game state lives server-side in the session so results can't be forged
 * by the client. Guests keep credits in the session; logged-in users have
 * credits persisted and every completed hand recorded as a score.
 */
class GameController extends Controller
{
    private const START_CREDITS = 100;

    public function index(Request $request)
    {
        return view('game', [
            'credits' => $this->credits($request),
            'paytable' => HandEvaluator::PAYTABLE,
        ]);
    }

    public function deal(Request $request): JsonResponse
    {
        $bet = (int) $request->validate(['bet' => 'required|integer|min:1|max:5'])['bet'];
        $credits = $this->credits($request);

        // An abandoned hand (e.g. page reload) is forfeited; its bet was already taken.
        $request->session()->forget('poker');
        if ($bet > $credits) {
            return response()->json(['error' => 'Not enough credits.'], 422);
        }

        $deck = HandEvaluator::newDeck();
        $hand = array_splice($deck, 0, 5);
        $this->setCredits($request, $credits - $bet);

        $request->session()->put('poker', ['deck' => $deck, 'hand' => $hand, 'bet' => $bet]);

        return response()->json([
            'hand' => $hand,
            'credits' => $credits - $bet,
            'preview' => HandEvaluator::evaluate($hand),
        ]);
    }

    public function draw(Request $request): JsonResponse
    {
        $data = $request->validate(['hold' => 'array|max:5', 'hold.*' => 'integer|between:0,4']);
        $state = $request->session()->pull('poker');

        if (! $state || empty($state['hand'])) {
            return response()->json(['error' => 'Deal a hand first.'], 422);
        }

        $hold = array_unique($data['hold'] ?? []);
        $hand = $state['hand'];
        foreach (range(0, 4) as $i) {
            if (! in_array($i, $hold, true)) {
                $hand[$i] = array_shift($state['deck']);
            }
        }

        $result = HandEvaluator::evaluate($hand);
        $payout = $result ? HandEvaluator::PAYTABLE[$result] * $state['bet'] : 0;
        if ($result === 'Royal Flush' && $state['bet'] === 5) {
            $payout = 4000; // classic max-bet royal bonus
        }

        $credits = $this->credits($request) + $payout;
        $this->setCredits($request, $credits);

        if ($user = $request->user()) {
            $user->scores()->create([
                'hand' => $result ?? 'No Win',
                'bet' => $state['bet'],
                'payout' => $payout,
                'credits_after' => $credits,
            ]);
        }

        return response()->json([
            'hand' => $hand,
            'result' => $result,
            'payout' => $payout,
            'credits' => $credits,
        ]);
    }

    /** Refill to the starting bankroll when broke. */
    public function reset(Request $request): JsonResponse
    {
        $request->session()->forget('poker');
        if ($this->credits($request) > 0) {
            return response()->json(['error' => 'You still have credits.'], 422);
        }
        $this->setCredits($request, self::START_CREDITS);

        return response()->json(['credits' => self::START_CREDITS]);
    }

    private function credits(Request $request): int
    {
        if ($user = $request->user()) {
            return $user->credits;
        }

        return $request->session()->get('guest_credits', self::START_CREDITS);
    }

    private function setCredits(Request $request, int $credits): void
    {
        if ($user = $request->user()) {
            /** @var User $user */
            $user->credits = $credits;
            $user->best_credits = max($user->best_credits, $credits);
            $user->save();

            return;
        }
        $request->session()->put('guest_credits', $credits);
    }
}
