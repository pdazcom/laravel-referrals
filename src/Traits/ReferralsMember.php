<?php

namespace Pdazcom\Referrals\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Pdazcom\Referrals\Events\UserReferred;
use Pdazcom\Referrals\Models\ReferralLink;
use Pdazcom\Referrals\Models\ReferralProgram;

/**
 * Trait ReferralsMember
 * @package Pdazcom\Referrals\Traits
 */
trait ReferralsMember {

    public function getReferrals(): Collection|\Illuminate\Support\Collection
    {
        return ReferralProgram::all()->map(function ($program) {
            return ReferralLink::getReferral($this, $program);
        })->filter();
    }

    public function referralProgram(): HasOne
    {
        return $this->hasOne(ReferralProgram::class, 'id', 'referral_program_id');
    }

    public function registerWithCode(string $code): bool
    {
        $referralLink = ReferralLink::findByAnyCode($code);

        if ($referralLink === null) {
            return false;
        }

        $expiresAt = now()->addMinutes($referralLink->program->lifetime_minutes)->timestamp;

        UserReferred::dispatch([$referralLink->id => $expiresAt], $this);

        return true;
    }

}
