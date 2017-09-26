<?php

namespace Pdazcom\Referrals\Tests\Events;

use Pdazcom\Referrals\Tests\TestCase;

class UserReferredTest extends TestCase
{
    public function testCreate()
    {
        $user = new \stdClass();
        $event = new \Pdazcom\Referrals\Events\UserReferred(1, $user);

        $this->assertEquals(1, $event->referralId);
        $this->assertEquals($user, $event->user);
    }
}