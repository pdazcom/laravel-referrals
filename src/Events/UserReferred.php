<?php

namespace Pdazcom\Referrals\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event that attach referrals
 */
class UserReferred
{
    use SerializesModels, Dispatchable;

    public string|int $referralId;
    public Model $user;

    public function __construct($referralId, Model $user)
    {
        $this->referralId = $referralId;
        $this->user = $user;
    }
}
