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
        $cookie = config('referrals.cookie_name', 'ref');

        if ($request->has($cookie)) {
            /** @var ReferralLink $referral */
            $referral = ReferralLink::whereCode($request->get($cookie))->first();

            if (!empty($referral)) {

                $referral->addClick();

                /** @var ReferralProgram $program */
                $program = $referral->program()->first();

                return redirect($request->url())->cookie($cookie, $referral->id, $program->lifetime_minutes);
            }

            Log::warning('Referral Ref code not found where request.ref equals ' . $request->get($cookie));
        }

        return $next($request);
    }
}
