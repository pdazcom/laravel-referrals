<?php

namespace Pdazcom\Referrals\Programs;

use Pdazcom\Referrals\Contracts\ProgramInterface;
use Pdazcom\Referrals\Models\ReferralProgram;

abstract class AbstractProgram implements ProgramInterface {
    public function __construct(
        protected ReferralProgram $program,

        /**
         * User who attracted the referral.
         */
        protected $recruitUser,

        /**
         * Attracted user
         */
        protected $referralUser
    )
    {}

}
