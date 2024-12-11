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

    public array $referralIDs;
    public Model $user;

    public function __construct(array $referralId, Model $user)
    {
        $this->referralIDs = array_keys($referralId);
        $this->user = $user;
    }
}
