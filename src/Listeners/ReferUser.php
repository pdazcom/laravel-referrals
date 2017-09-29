<?php

namespace Pdazcom\Referrals\Listeners;

use Pdazcom\Referrals\Events\UserReferred;
use Pdazcom\Referrals\Models\ReferralLink;

class ReferUser {

    public function handle(UserReferred $event)
    {
        \Log::debug('ReferUser listener: UserReferred fired!');
        $referralLink = ReferralLink::find($event->referralId);

        if (empty($referralLink)) {
            return;
        }

        $referralLink->relationships()->create(['user_id' => $event->user->id]);
    }
}