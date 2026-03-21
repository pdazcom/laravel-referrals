<?php

namespace Pdazcom\Referrals\Tests;

use Illuminate\Foundation\Auth\User;
use Pdazcom\Referrals\Traits\ReferralsMember;

class TestUser extends User {
    use ReferralsMember;

    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password'];
}
