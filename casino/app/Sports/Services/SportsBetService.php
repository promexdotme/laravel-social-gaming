<?php

namespace VanguardLTE\Sports\Services;

use VanguardLTE\Sports\Bet;
use VanguardLTE\Sports\BetItem;
use VanguardLTE\Sports\Outcome;
use VanguardLTE\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SportsBetService
{
    protected $ledger;

    public function __construct(WalletLedgerService $ledger)
    {
        $this->ledger = $ledger;
    }

    /**
     * Place a single or multi bet.
     *
     * @param User $user
     * @param array $items Array of ['outcome_id' => X, 'stake_amount' => Y]
     * @param int $type 1 = Single, 2 = Multi
     * @param float|null $multiStake Required if type = 2
     * @return Bet
     */
    public function placeBet(User $user, array $items, int $type, ?float $multiStake = null): Bet
    {
        if (empty($items)) {
            throw new \Exception("No selections in bet slip.");
        }

        if ($type === 2 && count($items) < 2) {
            throw new \Exception("Multi bets require at least 2 selections.");
        }

        return DB::transaction(function () use ($user, $items, $type, $multiStake) {
            $outcomeIds = collect($items)->pluck('outcome_id')->toArray();
            $outcomes = Outcome::availableForBet()->whereIn('id', $outcomeIds)->get();

            if (count($outcomes) !== count($items)) {
                throw new \Exception("One or more selections are no longer available for betting.");
            }

            $totalStake = 0.0;
            if ($type === 1) {
                foreach ($items as $item) {
                    $totalStake += (float) $item['stake_amount'];
                }
            } else {
                $totalStake = (float) $multiStake;
            }

            if ($totalStake <= 0) {
                throw new \Exception("Stake amount must be greater than 0.");
            }

            $minLimit = $type === 1 ? (float) settings('single_bet_min_limit', 1.0) : (float) settings('multi_bet_min_limit', 1.0);
            $maxLimit = $type === 1 ? (float) settings('single_bet_max_limit', 10000.0) : (float) settings('multi_bet_max_limit', 10000.0);

            if ($totalStake < $minLimit || $totalStake > $maxLimit) {
                throw new \Exception("Stake must be between {$minLimit} and {$maxLimit}.");
            }

            if ($user->balance < $totalStake) {
                throw new \Exception("Insufficient balance.");
            }

            if ($type === 1) {
                $betsCreated = [];
                foreach ($items as $item) {
                    $outcome = $outcomes->firstWhere('id', $item['outcome_id']);
                    $itemStake = (float) $item['stake_amount'];
                    $itemReturn = $itemStake * (float) $outcome->odds;

                    $singleBet = new Bet();
                    $singleBet->bet_number = strtoupper(Str::random(12));
                    $singleBet->user_id = $user->id;
                    $singleBet->type = 1;
                    $singleBet->stake_amount = $itemStake;
                    $singleBet->return_amount = $itemReturn;
                    $singleBet->status = 2; // Pending
                    $singleBet->is_settled = 0;
                    $singleBet->save();

                    $betItem = new BetItem();
                    $betItem->bet_id = $singleBet->id;
                    $betItem->market_id = $outcome->market_id;
                    $betItem->outcome_id = $outcome->id;
                    $betItem->odds = $outcome->odds;
                    $betItem->status = 2; // Pending
                    $betItem->save();

                    $betsCreated[] = $singleBet;
                }

                $this->ledger->debit($user, $totalStake, "Sports Single Bet Placement", 'sports_bet_stake');

                return end($betsCreated);
            } else {
                $bet = new Bet();
                $bet->bet_number = strtoupper(Str::random(12));
                $bet->user_id = $user->id;
                $bet->type = 2;
                $bet->stake_amount = $totalStake;
                $bet->status = 2; // Pending
                $bet->is_settled = 0;
                $bet->save();

                $multiplier = 1.0;
                foreach ($outcomes as $outcome) {
                    $multiplier *= (float) $outcome->odds;

                    $betItem = new BetItem();
                    $betItem->bet_id = $bet->id;
                    $betItem->market_id = $outcome->market_id;
                    $betItem->outcome_id = $outcome->id;
                    $betItem->odds = $outcome->odds;
                    $betItem->status = 2; // Pending
                    $betItem->save();
                }

                $totalReturn = $totalStake * $multiplier;
                $bet->return_amount = $totalReturn;
                $bet->save();

                $this->ledger->debit($user, $totalStake, "Sports Multi Bet Placement", 'sports_bet_stake', $bet->id);
                return $bet;
            }
        });
    }
}
