<?php

namespace Pdazcom\Referrals\Exceptions;

use RuntimeException;

class ReferralCodeGenerationException extends RuntimeException
{
    public static function maxAttemptsExceeded(int $attempts): static
    {
        return new static("Failed to generate a unique referral code after {$attempts} attempts.");
    }
}
