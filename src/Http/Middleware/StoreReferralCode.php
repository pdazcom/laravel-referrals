<?php

namespace Pdazcom\Referrals\Http\Middleware;

use Illuminate\Http\Request;
use Pdazcom\Referrals\Models\ReferralLink;

/**
 * Class StoreReferralCode
 * @package Pdazcom\Referrals\Http\Middleware
 */
class StoreReferralCode {

    public function handle(Request $request, \Closure $next)
    {
        if ($request->has('ref')){
            $referral = ReferralLink::whereCode($request->get('ref'))->first();

            if (!empty($referral)) {
                return redirect($request->url())->cookie('ref', $referral->id, $referral->lifetime_minutes);
            }
        }

        return $next($request);
    }
}