<?php

namespace Pdazcom\Referrals\Programs;

/**
 * This referral program simply adds 30% to the balance of the referral link owner
 */
class ExampleProgram extends AbstractProgram {

    const ROYALTY_PERCENT = 30;

    public function reward($rewardObject): void
    {
        $this->recruitUser->balance = $this->recruitUser->balance + $rewardObject * (self::ROYALTY_PERCENT/100);
        $this->recruitUser->save();
    }

}
