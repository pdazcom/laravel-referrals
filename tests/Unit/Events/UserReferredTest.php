<?php

namespace Pdazcom\Referrals\Tests\Unit\Events;

use Pdazcom\Referrals\Events\UserReferred;
use Pdazcom\Referrals\Tests\TestCase;
use Pdazcom\Referrals\Tests\TestUser;

class UserReferredTest extends TestCase
{
    public function testCreate()
    {
        $link_id = fake()->randomNumber();
        $user = new TestUser();
        $event = new UserReferred([
            1 => now()->addDay()->timestamp,
            2 => now()->addMinute()->timestamp,
            $link_id => now()->timestamp
        ], $user);

        $this->assertTrue(in_array($link_id, $event->referralIDs));
        $this->assertEquals($user, $event->user);
    }
}
