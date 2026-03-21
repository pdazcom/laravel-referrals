<?php

namespace Pdazcom\Referrals\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Pdazcom\Referrals\Contracts\ReferralCodeGeneratorInterface;
use Pdazcom\Referrals\Exceptions\ReferralCodeGenerationException;
use Ramsey\Uuid\Uuid;

/**
 * Class ReferralLink
 * @package Pdazcom\Referrals\Models
 * @property int $id
 * @property string $code
 * @property string|null $referral_code
 * @property Collection $relationships
 * @property ReferralProgram $program
 */
class ReferralLink extends Model
{
    protected $fillable = ['user_id', 'referral_program_id', 'referral_code'];

    protected static function boot(): void
    {
        // Required to call super method first - https://github.com/laravel/framework/issues/25455
        parent::boot();

        static::creating(function (ReferralLink $model) {
            $model->generateCode();
            $model->generateReferralCode();
        });
    }

    private function generateCode(): void
    {
        $this->code = (string) Uuid::uuid1();
    }

    private function generateReferralCode(): void
    {
        if ($this->referral_code !== null) {
            return;
        }

        $generator = app(ReferralCodeGeneratorInterface::class);
        $maxAttempts = config('referrals.code_generation_max_attempts', 10);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $candidate = $generator->generate();

            if (!static::where('referral_code', $candidate)->exists()) {
                $this->referral_code = $candidate;
                return;
            }
        }

        throw ReferralCodeGenerationException::maxAttemptsExceeded($maxAttempts);
    }

    public function assignReferralCode(string $referralCode): static
    {
        $this->referral_code = $referralCode;
        $this->save();
        return $this;
    }

    public static function getReferral($user, $program): ?static
    {
        return static::where([
            'user_id' => $user->id,
            'referral_program_id' => $program->id
        ])->first();
    }

    public static function findByReferralCode(string $referralCode): ?static
    {
        return static::where('referral_code', $referralCode)->first();
    }

    public function link(): Attribute
    {
        return Attribute::get(fn () => url($this->program->uri) . '?ref=' . $this->code);
    }

    public function referralLink(): Attribute
    {
        return Attribute::get(function () {
            if ($this->referral_code === null) {
                return null;
            }
            return url($this->program->uri) . '?ref=' . $this->referral_code;
        });
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

    public function addClick(): static
    {
        $this->increment('clicks');
        return $this;
    }
}
