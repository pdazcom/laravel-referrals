<?php

namespace Pdazcom\Referrals\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Pdazcom\Referrals\Models\ReferralLink;
use Pdazcom\Referrals\Models\ReferralProgram;

/**
 * Class StoreReferralCode
 * @package Pdazcom\Referrals\Http\Middleware
 *
 * This middleware store referral link in cookies
 */
class StoreReferralCode {

    public function handle(Request $request, Closure $next)
    {
        if ($request->has('ref')){

            /** @var ReferralLink $referral */
            $referral = ReferralLink::whereCode($request->get('ref'))->first();

            /** @var ReferralProgram $program */
            $program = $referral->program()->first();

            if (!empty($referral)) {
                return redirect($request->url())->cookie('ref', $referral->id, $program->lifetime_minutes);
            } else {
                Log::warning('Referral Ref code not found where request.ref equals ' . $request->get('ref'));
            }
        }

        return $next($request);
    }
}
