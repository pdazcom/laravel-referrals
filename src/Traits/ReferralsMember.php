<?php

namespace Pdazcom\Referrals\Traits;

use Pdazcom\Referrals\Models\ReferralLink;
use Pdazcom\Referrals\Models\ReferralProgram;

/**
 * Trait ReferralsMember
 * @package Pdazcom\Referrals\Traits
 */
trait ReferralsMember {

    public function getReferrals()
    {
        return ReferralProgram::all()->map(function ($program) {
            return ReferralLink::getReferral($this, $program);
        })->filter();
    }

    public function referralProgram()
    {
        return $this->hasOne(ReferralProgram::class, 'id', 'referral_program_id');
    }

}