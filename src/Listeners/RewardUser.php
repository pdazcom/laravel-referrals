<?php

namespace Pdazcom\Referrals\Listeners;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Pdazcom\Referrals\Events\ReferralCase;
use Pdazcom\Referrals\Models\ReferralLink;
use Pdazcom\Referrals\Models\ReferralProgram;
use Pdazcom\Referrals\Models\ReferralRelationship;

/**
 * Reward accrual
 */
class RewardUser {

    public function handle(ReferralCase $event): void
    {
        $referralPrograms = ReferralProgram::whereIn('name', $event->programName)->get();

        if (empty($referralPrograms)) {
            Log::warning("Program(s) named '" . implode(", ", $event->programName) . "' not found");
            return;
        }

        foreach ($referralPrograms as $referralProgram) {
            $referralLink = $this->getReferralLink($referralProgram, $event->user->id);

            if (empty($referralLink)) {
                continue;
            }

            $relationship = $this->getRelationship($referralLink, $event->user->id);

            if ($this->isDuplicateReward($relationship)) {
                Log::info("Duplicate reward skipped for user {$event->user->id} on program '{$referralProgram->name}'");
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

            if ($relationship !== null && config('referrals.prevent_duplicate_rewards', false)) {
                $relationship->markAsRewarded();
            }
        }
    }

    /**
     * Find referral link where current user is refer for
     *
     * @param ReferralProgram $program
     * @param $userId
     * @return Builder|Model|HasMany|null
     */
    protected function getReferralLink(ReferralProgram $program, $userId): Builder|Model|HasMany|null
    {
        return $program->links()->whereHas('relationships', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->first();
    }

    protected function getRelationship(ReferralLink $referralLink, int $userId): ?ReferralRelationship
    {
        return $referralLink->relationships()->where('user_id', $userId)->first();
    }

    private function isDuplicateReward(?ReferralRelationship $relationship): bool
    {
        if (!config('referrals.prevent_duplicate_rewards', false)) {
            return false;
        }

        return $relationship !== null && $relationship->isRewarded();
    }
}
