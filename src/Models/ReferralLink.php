<?php

namespace Pdazcom\Referrals\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
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

    private bool $referralCodeManuallySet = false;

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
        if ($this->code !== null) {
            return;
        }

        $this->code = (string) Uuid::uuid1();
    }

    private function generateReferralCode(): void
    {
        if ($this->referral_code !== null) {
            if ($this->codeExistsInAnyColumn($this->referral_code)) {
                throw new \InvalidArgumentException(
                    "The referral code '{$this->referral_code}' is already in use."
                );
            }
            $this->referralCodeManuallySet = true;
            return;
        }

        $generator = app(ReferralCodeGeneratorInterface::class);
        $maxAttempts = config('referrals.code_generation_max_attempts', 10);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $candidate = $generator->generate();

            if (!$this->codeExistsInAnyColumn($candidate)) {
                $this->referral_code = $candidate;
                return;
            }
        }

        throw ReferralCodeGenerationException::maxAttemptsExceeded($maxAttempts);
    }

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            return parent::save($options);
        }

        $maxAttempts = config('referrals.code_generation_max_attempts', 10);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return parent::save($options);
            } catch (QueryException $e) {
                if (!$this->isUniqueConstraintViolation($e)) {
                    throw $e;
                }

                if ($this->referralCodeManuallySet) {
                    throw new \InvalidArgumentException(
                        "The referral code '{$this->referral_code}' is already in use."
                    );
                }

                if ($attempt >= $maxAttempts) {
                    throw $e;
                }

                $this->referral_code = null;
                $this->generateReferralCode();
            }
        }

        throw ReferralCodeGenerationException::maxAttemptsExceeded($maxAttempts);
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        if (class_exists('Illuminate\Database\UniqueConstraintViolationException')
            && $e instanceof \Illuminate\Database\UniqueConstraintViolationException) {
            return true;
        }

        $errorCode = $e->errorInfo[1] ?? null;
        return $errorCode === 1062 || $errorCode === 2627 || $errorCode === 19;
    }

    public function assignReferralCode(string $referralCode): static
    {
        if (static::codeExistsInAnyColumn($referralCode)) {
            throw new \InvalidArgumentException(
                "The referral code '{$referralCode}' is already in use."
            );
        }

        $this->referral_code = $referralCode;
        $this->referralCodeManuallySet = true;
        $this->save();
        return $this;
    }

    private static function codeExistsInAnyColumn(string $candidate): bool
    {
        return static::where('referral_code', $candidate)->exists()
            || static::where('code', $candidate)->exists();
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

    public static function findByAnyCode(string $code): ?static
    {
        return static::where('code', $code)->first()
            ?? static::where('referral_code', $code)->first();
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
