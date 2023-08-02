<?php

namespace Pdazcom\Referrals\Contracts;

/**
 *
 */
interface ProgramInterface {

    /**
     * Handler function for reward users
     *
     * @param mixed $rewardObject
     * @return void
     */
    public function reward(mixed $rewardObject): void;
}
