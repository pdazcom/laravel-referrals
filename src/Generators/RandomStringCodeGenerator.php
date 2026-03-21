<?php

namespace Pdazcom\Referrals\Generators;

use Pdazcom\Referrals\Contracts\ReferralCodeGeneratorInterface;

class RandomStringCodeGenerator implements ReferralCodeGeneratorInterface
{
    public function __construct(
        private readonly int $length = 8,
        private readonly string $charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
    ) {}

    public function generate(): string
    {
        $charsetLength = strlen($this->charset);
        $code = '';

        for ($i = 0; $i < $this->length; $i++) {
            $code .= $this->charset[random_int(0, $charsetLength - 1)];
        }

        return $code;
    }
}
