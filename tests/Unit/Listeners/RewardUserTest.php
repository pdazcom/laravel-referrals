<?php

namespace Pdazcom\Referrals\Tests\Unit\Listeners;

use Pdazcom\Referrals\Events\ReferralCase;
use Pdazcom\Referrals\Listeners\RewardUser;
use Pdazcom\Referrals\Models\ReferralProgram;
use Pdazcom\Referrals\Programs\ExampleProgram;
use Pdazcom\Referrals\Tests\WithLoadMigrations;
use Pdazcom\Referrals\Tests\TestCase;
use Mockery as m;

class RewardUserTest extends TestCase
{
    use WithLoadMigrations;

    public function testRewardUser()
    {
        $recruitUser = m::mock($this->user());
        $recruitUser->balance = 100;

        $referralUser = $this->user();

        $program = ReferralProgram::create([
            'name' => 'example',
            'title' => 'Test',
            'description' => 'Test description',
            'uri' => 'test',
        ]);

        $refLink = $program->links()->create([
            'user_id' => $recruitUser->id,
        ]);

        $refLink->relationships()->create([
            'user_id' => $referralUser->id,
        ]);
        $refLink->setRelation('user', $recruitUser);

        $event = new ReferralCase('example', $referralUser, 350);

        $recruitUser->shouldReceive('save')->once();
        $mockRewardUser = m::mock(RewardUser::class)->makePartial();

        $mockRewardUser->shouldAllowMockingProtectedMethods();
        $mockRewardUser->shouldReceive('getReferralLink')->once()->andReturn($refLink);
        $mockRewardUser->handle($event);

        $this->assertEquals(100 + (350 * ExampleProgram::ROYALTY_PERCENT / 100), $recruitUser->balance);
    }
}
