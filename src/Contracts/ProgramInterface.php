<?php

namespace Pdazcom\Referrals\Contracts;

interface ProgramInterface {

    /**
     * Handler function for reward users
     *
     * @param mixed
     * @return mixed
     */
    public function reward($rewardObject);
}