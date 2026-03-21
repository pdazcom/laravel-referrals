<?php

namespace Pdazcom\Referrals\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Pdazcom\Referrals\Events\UserReferred;
use Pdazcom\Referrals\Http\Middleware\StoreReferralCode;

class OnRegisterListener
{
    public function __construct(protected Request $request) {}

    public function handle(Registered $event): void
    {
        $referrals = $this->request->input(StoreReferralCode::REFERRALS, []);

        if (empty($referrals)) {
            return;
        }

        UserReferred::dispatch($referrals, $event->user);
    }
}
