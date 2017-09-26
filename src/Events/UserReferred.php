<?php

namespace Pdazcom\Referrals\Events;

use Illuminate\Queue\SerializesModels;

class UserReferred
{
    use SerializesModels;

    public $referralId;
    public $user;

    public function __construct($referralId, $user)
    {
        $this->referralId = $referralId;
        $this->user = $user;
    }

    public function broadcastOn()
    {
        return [];
    }
}