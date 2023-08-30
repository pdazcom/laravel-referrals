<?php

namespace Pdazcom\Referrals\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The event that triggers the reward
 */
class ReferralCase {

    use SerializesModels, Dispatchable;

    public array $programName;
    public Model $user;
    public $rewardObject;

    public function __construct($programName, Model $user, $rewardObject)
    {
        $this->user = $user;
        $this->programName = is_array($programName) ? $programName : [ $programName ];
        $this->rewardObject = $rewardObject;
    }
}
