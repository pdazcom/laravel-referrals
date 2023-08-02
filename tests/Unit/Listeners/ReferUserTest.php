<?php

namespace Pdazcom\Referrals\Tests\Unit\Listeners;

use Pdazcom\Referrals\Events\UserReferred;
use Pdazcom\Referrals\Listeners\ReferUser;
use Pdazcom\Referrals\Models\ReferralProgram;
use Pdazcom\Referrals\Tests\TestCase;
use Pdazcom\Referrals\Tests\WithLoadMigrations;

class ReferUserTest extends TestCase
{
    use WithLoadMigrations;

    public function testCreatingRelationship()
    {
        $recruitUser = $this->user();
        $referrerUser = $this->user();

        $program = ReferralProgram::create([
            'name' => 'test',
            'title' => 'Test',
            'description' => 'Test description',
            'uri' => 'test',
        ]);

        $refLink = $program->links()->create([
            'user_id' => $recruitUser->id,
        ]);

        $event = new UserReferred($refLink->id, $referrerUser);
        (new ReferUser())->handle($event);

        $relationships = $refLink->relationships;

        $this->assertCount(1, $relationships);
        $this->assertEquals($referrerUser->id, $relationships->first()->user_id);
    }
}
