<?php

namespace Pdazcom\Referrals\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

/**
 * Class ReferralLink
 * @package Pdazcom\Referrals\Models
 * @property int $id
 * @property string $code
 * @property Collection $relationships
 * @property ReferralProgram $program
 */
class ReferralLink extends Model
{
    protected $fillable = ['user_id', 'referral_program_id'];

    protected static function boot()
    {
        // Required to call super method first - https://github.com/laravel/framework/issues/25455
        parent::boot();

        static::creating(function (ReferralLink $model) {
            $model->generateCode();
        });
    }

    private function generateCode()
    {
        $this->code = (string) Uuid::uuid1();
    }

    public static function getReferral($user, $program)
    {
        return static::where([
            'user_id' => $user->id,
            'referral_program_id' => $program->id
        ])->first();
    }

    public function link(): Attribute
    {
        return Attribute::get( fn () => url($this->program->uri) . '?ref=' . $this->code);
    }

    public function user(): BelongsTo
    {
        $usersModel = config('auth.providers.users.model');
        return $this->belongsTo($usersModel);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ReferralProgram::class, 'referral_program_id');
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(ReferralRelationship::class);
    }

    public function referredUsers()
    {
        $usersModel = config('auth.providers.users.model');
        return $usersModel::whereIn('id', $this->relationships->pluck('user_id')->all())->get();
    }

    public function addClick()
    {
        $this->increment('clicks');
        return $this;
    }
}
