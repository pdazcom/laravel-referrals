<?php

namespace Pdazcom\Referrals\Programs;

use Pdazcom\Referrals\Contracts\ProgramInterface;
use Pdazcom\Referrals\Models\ReferralProgram;

abstract class AbstractProgram implements ProgramInterface {

    /**
     * @var ReferralProgram
     */
    protected $program;

    /**
     * User who attracted the referral.
     *
     * @var mixed
     */
    protected $recruitUser;

    /**
     * Attracted user
     *
     * @var
     */
    protected $referralUser;

    public function __construct(ReferralProgram $program, $recruitUser, $referralUser)
    {
        $this->program = $program;
        $this->recruitUser = $recruitUser;
        $this->referralUser = $referralUser;
    }

}