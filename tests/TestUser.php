<?php

namespace Pdazcom\Referrals\Tests;

use Illuminate\Foundation\Auth\User;

class TestUser extends User {
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password'];
}
