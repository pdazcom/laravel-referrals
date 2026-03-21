<?php

namespace Pdazcom\Referrals\Tests\Unit\Programs;

use Pdazcom\Referrals\Events\ReferralCase;
use Pdazcom\Referrals\Listeners\RewardUser;
use Pdazcom\Referrals\Models\ReferralProgram;
use Pdazcom\Referrals\Programs\FixedRewardProgram;
use Pdazcom\Referrals\Tests\TestCase;
use Pdazcom\Referrals\Tests\WithLoadMigrations;
use Mockery as m;

class FixedRewardProgramTest extends TestCase
{
    use WithLoadMigrations;

    public function testRewardsFixedAmountFromConfig(): void
    {
        $this->app['config']->set('referrals.fixed_reward_amount', 15);
        $this->app['config']->set('referrals.programs.fixed-bonus', FixedRewardProgram::class);

        $recruitUser = m::mock($this->user());
        $recruitUser->balance = 100;

        $referralUser = $this->user();

        $program = ReferralProgram::create([
            'name'        => 'fixed-bonus',
            'title'       => 'Fixed Bonus',
            'description' => 'Test',
            'uri'         => 'test',
        ]);

        $refLink = $program->links()->create(['user_id' => $recruitUser->id]);
        $refLink->relationships()->create(['user_id' => $referralUser->id]);
        $refLink->setRelation('user', $recruitUser);

        $recruitUser->shouldReceive('save')->once();

        $mockRewardUser = m::mock(RewardUser::class)->makePartial();
        $mockRewardUser->shouldAllowMockingProtectedMethods();
        $mockRewardUser->shouldReceive('getReferralLink')->once()->andReturn($refLink);

        $event = new ReferralCase('fixed-bonus', $referralUser, 999);
        $mockRewardUser->handle($event);

        $this->assertEquals(115, $recruitUser->balance);
    }

    public function testConstantAmountMatchesDefaultConfig(): void
    {
        $this->assertEquals(
            FixedRewardProgram::FIXED_AMOUNT,
            config('referrals.fixed_reward_amount', FixedRewardProgram::FIXED_AMOUNT)
        );
    }

    public function testRewardObjectValueIsIgnored(): void
    {
        $this->app['config']->set('referrals.fixed_reward_amount', 20);
        $this->app['config']->set('referrals.programs.fixed-bonus', FixedRewardProgram::class);

        $recruitUser1 = m::mock($this->user());
        $recruitUser1->balance = 0;

        $recruitUser2 = m::mock($this->user());
        $recruitUser2->balance = 0;

        $referralUser1 = $this->user();
        $referralUser2 = $this->user();

        $program = ReferralProgram::create([
            'name'        => 'fixed-bonus',
            'title'       => 'Fixed Bonus',
            'description' => 'Test',
            'uri'         => 'test',
        ]);

        $refLink1 = $program->links()->create(['user_id' => $recruitUser1->id]);
        $refLink1->relationships()->create(['user_id' => $referralUser1->id]);
        $refLink1->setRelation('user', $recruitUser1);

        $refLink2 = $program->links()->create(['user_id' => $recruitUser2->id]);
        $refLink2->relationships()->create(['user_id' => $referralUser2->id]);
        $refLink2->setRelation('user', $recruitUser2);

        $recruitUser1->shouldReceive('save')->once();
        $recruitUser2->shouldReceive('save')->once();

        $mockRewardUser1 = m::mock(RewardUser::class)->makePartial();
        $mockRewardUser1->shouldAllowMockingProtectedMethods();
        $mockRewardUser1->shouldReceive('getReferralLink')->once()->andReturn($refLink1);

        $mockRewardUser2 = m::mock(RewardUser::class)->makePartial();
        $mockRewardUser2->shouldAllowMockingProtectedMethods();
        $mockRewardUser2->shouldReceive('getReferralLink')->once()->andReturn($refLink2);

        $mockRewardUser1->handle(new ReferralCase('fixed-bonus', $referralUser1, 1));
        $mockRewardUser2->handle(new ReferralCase('fixed-bonus', $referralUser2, 9999));

        $this->assertEquals(20, $recruitUser1->balance);
        $this->assertEquals(20, $recruitUser2->balance);
    }
}
