<?php

namespace Pdazcom\Referrals\Programs;

/**
 * Awards a fixed credit amount to the referral link owner on each qualifying conversion.
 *
 * Unlike ExampleProgram (which derives the reward from the rewardObject value),
 * this program always pays the same flat amount regardless of order size or event data.
 *
 * Usage — register in config/referrals.php:
 *
 *   'programs' => [
 *       'fixed-bonus' => \Pdazcom\Referrals\Programs\FixedRewardProgram::class,
 *   ],
 *
 * Then set the amount via config('referrals.fixed_reward_amount') or override the
 * FIXED_AMOUNT constant in your own subclass.
 */
class FixedRewardProgram extends AbstractProgram
{
    const FIXED_AMOUNT = 10;

    public function reward(mixed $rewardObject): void
    {
        $amount = config('referrals.fixed_reward_amount', static::FIXED_AMOUNT);

        $this->recruitUser->balance = $this->recruitUser->balance + $amount;
        $this->recruitUser->save();
    }
}
