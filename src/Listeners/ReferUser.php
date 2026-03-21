<?php

namespace Pdazcom\Referrals\Listeners;

use Illuminate\Support\Facades\Log;
use Pdazcom\Referrals\Events\UserReferred;
use Pdazcom\Referrals\Models\ReferralLink;

/**
 * Attaching referrals
 */
class ReferUser {

    public function handle(UserReferred $event): void
    {
        Log::debug('ReferUser listener: UserReferred fired!');
        if (empty($event->referralIDs)) {
            Log::debug('ReferralIDs not provided so skipping logic [' . implode(", ", $event->referralIDs) . ']');
            return;
        }

        foreach ($event->referralIDs as $referralID) {

            /** @var ReferralLink $referralLink */
            $referralLink = ReferralLink::find($referralID);

            if (empty($referralLink)) {
                Log::warning('Referral Link not found for referralId '. $referralID);
                continue;
            }

            if ($this->isSelfReferral($referralLink, $event->user->id)) {
                Log::info("Self-referral prevented for user {$event->user->id} on link {$referralID}");
                continue;
            }

            $referralLink->relationships()->firstOrCreate(['user_id' => $event->user->id]);
        }
    }

    private function isSelfReferral(ReferralLink $referralLink, int $userId): bool
    {
        if (!config('referrals.prevent_self_referral', false)) {
            return false;
        }

        return (int) $referralLink->user_id === $userId;
    }
}
