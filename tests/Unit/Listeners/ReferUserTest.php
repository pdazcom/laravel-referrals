<?php

namespace Pdazcom\Referrals\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Pdazcom\Referrals\Events\UserReferred;
use Pdazcom\Referrals\Listeners\ReferUser;
use Pdazcom\Referrals\Models\ReferralProgram;
use Pdazcom\Referrals\Tests\TestCase;
use Mockery as m;
use Pdazcom\Referrals\Tests\WithLoadMigrations;

class ReferUserTest extends TestCase
{
    use DatabaseTransactions;
    use DatabaseMigrations;
    use WithLoadMigrations;

    public function setUp()
    {
        parent::setUp();
        $this->loadMigrations();
    }

    public function testCreatingRelationship()
    {
        $recruitUser = m::mock(\stdClass::class);
        $recruitUser->id = 1;

        $referrerUser = m::mock(\stdClass::class);
        $referrerUser->id = 2;

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
