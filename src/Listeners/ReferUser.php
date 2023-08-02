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
        if (empty($event->referralId)) {
            Log::debug('ReferralId not provided so skipping logic'. $event->referralId);
            return;
        }

        $referralLink = ReferralLink::find($event->referralId);

        if (empty($referralLink)) {
            Log::warning('Referral Link not found for referralId '. $event->referralId);
            return;
        }

        $referralLink->relationships()->create(['user_id' => $event->user->id]);
    }
}
