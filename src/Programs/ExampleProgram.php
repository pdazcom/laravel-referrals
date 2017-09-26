<?php

namespace Pdazcom\Referrals\Programs;

class ExampleProgram extends AbstractProgram {

    const ROYALTY_PERCENT = 30;

    public function reward($rewardObject)
    {
        $this->recruitUser->balance = $this->recruitUser->balance + $rewardObject * (self::ROYALTY_PERCENT/100);
        $this->recruitUser->save();
    }

}