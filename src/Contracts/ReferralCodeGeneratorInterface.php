<?php

namespace Pdazcom\Referrals\Contracts;

interface ReferralCodeGeneratorInterface
{
    public function generate(): string;
}
