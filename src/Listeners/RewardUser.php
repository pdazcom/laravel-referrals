<?php

namespace Pdazcom\Referrals\Listeners;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Pdazcom\Referrals\Events\ReferralCase;
use Pdazcom\Referrals\Models\ReferralProgram;

/**
 * Reward accrual
 */
class RewardUser {

    public function handle(ReferralCase $event): void
    {
        // find needle referral program
        $referralPrograms = ReferralProgram::whereIn('name', $event->programName)->get();

        // if it not exists, then nothing to do
        if (empty($referralPrograms)) {
            Log::warning("Program(s) named '" . implode(", ", $event->programName) . "' not found");
            return;
        }

        foreach ($referralPrograms as $referralProgram) {
            $referralLink = $this->getReferralLink($referralProgram, $event->user->id);

            // if user is not refer for this referral program then nothing to do
            if (empty($referralLink)) {
                continue;
            }

            $recruitUser = $referralLink->user;
            $referralUser = $event->user;
            $rewardClass = config('referrals.programs.' . $referralProgram->name);

            if (!class_exists($rewardClass)) {
                Log::warning("Not configured program reward class for '$referralProgram->name' referral program");
                continue;
            }

            (new $rewardClass($referralProgram, $recruitUser, $referralUser))->reward($event->rewardObject);
        }
    }

    /**
     * Find referral link where current user is refer for
     *
     * @param $userId
     * @param ReferralProgram $program
     * @return Builder|Model|HasMany|null
     */
    protected function getReferralLink(ReferralProgram $program, $userId): Builder|Model|HasMany|null
    {
        return $program->links()->whereHas('relationships', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->first();
    }
}
