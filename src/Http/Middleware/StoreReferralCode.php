<?php

namespace Pdazcom\Referrals\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Pdazcom\Referrals\Models\ReferralLink;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Class StoreReferralCode
 * @package Pdazcom\Referrals\Http\Middleware
 *
 * This middleware store referral link in cookies and attach to $request
 * instance array of referral links with expiration date
 * [
 *  link_id1 => expires_timestamp,
 *  link_id2 => expires_timestamp,
 *  ...
 *  link_idN => expires_timestamp
 * ]
 */
class StoreReferralCode {

    protected string $cookieName = 'ref';
    const REFERRALS = '_referrals';
    protected Request $request;

    public function handle(Request $request, Closure $next)
    {
        $this->request = $request;
        $this->cookieName = config('referrals.cookie_name');
        $request->merge([static::REFERRALS => $this->parseRefCookie()]);

        if ($request->query($this->cookieName)) {
            /** @var ReferralLink $referral */
            $referral = ReferralLink::whereCode($request->get($this->cookieName))->first();

            if (!empty($referral)) {

                $referral->addClick();

                return redirect($request->url())
                    ->cookie($this->prepareCookie($referral->id, $referral->program->lifetime_minutes));
            }

            Log::warning('Referral Ref code not found where request.ref equals ' . $request->get($this->cookieName));
        }

        return $next($request);
    }

    /**
     * Parse referrals from cookie and returns as array like ['ref_id' => 'ref_expires']
     * where 'ref_id' is a referral link `id`
     * and 'ref_expires' is timestamp when referral expires
     *
     * @return array
     */
    public function parseRefCookie (): array
    {
        // get referral program cookie if exist
        $refCookie = $this->request->cookie($this->cookieName, '[]');
        try {
            $programCookie = json_decode($refCookie, true, flags: JSON_THROW_ON_ERROR);

            // remove all expired referrals
            return array_filter($programCookie, fn ($expires) => now()->timestamp < $expires);

        } catch (\Exception) {

            // return empty can't unknown format
            return [];
        }
    }

    private function prepareCookie(int $referralId, int $lifetimeMinutes): Cookie
    {
        $programCookie = $this->request->input(static::REFERRALS);

        // add referral ID to array
        // set cookie for current referral
        $programCookie[$referralId] = now()->addMinutes($lifetimeMinutes)->timestamp;

        // save to request new referrals
        $this->request->replace([static::REFERRALS => $programCookie]);

        // set cookie with max ref program lifetime
        $cookieExpires = max(array_values($programCookie));

        // add a minute for inclusive difference
        $lifetimeMinutes = Carbon::createFromTimestamp($cookieExpires)->diffInMinutes() + 1;

        return cookie(
            $this->cookieName,
            json_encode($programCookie),
            $lifetimeMinutes
        );
    }
}
