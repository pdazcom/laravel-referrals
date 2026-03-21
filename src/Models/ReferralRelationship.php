<?php

namespace Pdazcom\Referrals\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $referral_link_id
 * @property int $user_id
 * @property Carbon|null $rewarded_at
 */
class ReferralRelationship extends Model
{
    protected $fillable = ['referral_link_id', 'user_id', 'rewarded_at'];

    protected $casts = [
        'rewarded_at' => 'datetime',
    ];

    public function isRewarded(): bool
    {
        return $this->rewarded_at !== null;
    }

    public function markAsRewarded(): void
    {
        $this->update(['rewarded_at' => now()]);
    }
}
