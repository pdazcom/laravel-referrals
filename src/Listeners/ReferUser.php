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

            $referralLink->relationships()->firstOrCreate(['user_id' => $event->user->id]);
        }
    }
}
