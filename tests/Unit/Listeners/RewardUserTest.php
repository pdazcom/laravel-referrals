<?php

namespace Pdazcom\Referrals\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Orchestra\Testbench\Traits\WithLoadMigrationsFrom;
use Pdazcom\Referrals\Events\ReferralCase;
use Pdazcom\Referrals\Events\UserReferred;
use Pdazcom\Referrals\Listeners\ReferUser;
use Pdazcom\Referrals\Listeners\RewardUser;
use Pdazcom\Referrals\Models\ReferralProgram;
use Pdazcom\Referrals\Programs\ExampleProgram;
use Pdazcom\Referrals\Tests\TestCase;
use Mockery as m;

class RewardUserTest extends TestCase
{
    use WithLoadMigrationsFrom;
    use DatabaseTransactions;
    use DatabaseMigrations;

    public function setUp()
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');
    }

    public function testRewardUser()
    {
        $recruitUser = m::mock(\stdClass::class);
        $recruitUser->id = 1;
        $recruitUser->balance = 100;

        $referralUser = m::mock(\stdClass::class);
        $referralUser->id = 2;

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
        $mockRewardUser = m::mock(RewardUser::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $mockRewardUser->shouldReceive('getReferralLink')->once()->andReturn($refLink);
        $mockRewardUser->handle($event);

        $this->assertEquals(100 + (350 * ExampleProgram::ROYALTY_PERCENT / 100), $recruitUser->balance);
    }
}
