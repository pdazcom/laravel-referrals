<?php

namespace Pdazcom\Referrals\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class ReferralProgram
 * @package Pdazcom\Referrals\Models
 * @property int $id
 * @property string $uri
 * @property string $name
 * @property string $title
 * @property string $description
 * @property int $lifetime_minutes
 */
class ReferralProgram extends Model {

    /**
     * @var array
     */
    protected $fillable = ['name', 'uri', 'lifetime_minutes', 'title', 'description'];

    public function links(): HasMany
    {
        return $this->hasMany(ReferralLink::class, 'referral_program_id', 'id');
    }

}
