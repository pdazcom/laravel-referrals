<?php

namespace Pdazcom\Referrals\Tests\Unit\Events;

use Pdazcom\Referrals\Events\UserReferred;
use Pdazcom\Referrals\Tests\TestCase;
use Pdazcom\Referrals\Tests\TestUser;

class UserReferredTest extends TestCase
{
    public function testCreate()
    {
        $user = new TestUser();
        $event = new UserReferred(1, $user);

        $this->assertEquals(1, $event->referralId);
        $this->assertEquals($user, $event->user);
    }
}
