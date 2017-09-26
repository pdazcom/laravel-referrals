<?php

namespace Pdazcom\Referrals\Events;

use Illuminate\Queue\SerializesModels;

class ReferralCase {

    use SerializesModels;

    public $programName;
    public $user;
    public $rewardObject;

    public function __construct($programName, $user, $rewardObject)
    {
        $this->user = $user;
        $this->programName = $programName;
        $this->rewardObject = $rewardObject;
    }

    public function broadcastOn()
    {
        return [];
    }
}