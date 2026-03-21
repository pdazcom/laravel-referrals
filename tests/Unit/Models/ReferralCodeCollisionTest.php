<?php

namespace Pdazcom\Referrals\Tests\Unit\Models;

use Pdazcom\Referrals\Contracts\ReferralCodeGeneratorInterface;
use Pdazcom\Referrals\Exceptions\ReferralCodeGenerationException;
use Pdazcom\Referrals\Models\ReferralLink;
use Pdazcom\Referrals\Models\ReferralProgram;
use Pdazcom\Referrals\Tests\TestCase;
use Pdazcom\Referrals\Tests\WithLoadMigrations;

class ReferralCodeCollisionTest extends TestCase
{
    use WithLoadMigrations;

    private ReferralProgram $program;

    protected function setUp(): void
    {
        parent::setUp();

        $this->program = ReferralProgram::create([
            'name' => 'example',
            'title' => 'Test Program',
            'description' => 'Test description',
            'uri' => 'test',
        ]);
    }

    public function testRetryOnCollisionEventuallySucceeds(): void
    {
        $callCount = 0;
        $collisionCode = 'COLLIDE1';

        $this->program->links()->create([
            'user_id' => 1,
            'referral_code' => $collisionCode,
        ]);

        $this->app->bind(ReferralCodeGeneratorInterface::class, function () use (&$callCount, $collisionCode) {
            return new class($callCount, $collisionCode) implements ReferralCodeGeneratorInterface {
                public function __construct(private int &$callCount, private string $collisionCode) {}

                public function generate(): string
                {
                    $this->callCount++;
                    if ($this->callCount === 1) {
                        return $this->collisionCode;
                    }
                    return 'UNIQUE-OK';
                }
            };
        });

        $newLink = $this->program->links()->create(['user_id' => 2]);

        $this->assertEquals('UNIQUE-OK', $newLink->referral_code);
        $this->assertEquals(2, $callCount, 'Generator should be called twice: once for collision, once for success');
    }

    public function testExceptionThrownWhenMaxAttemptsExceeded(): void
    {
        config(['referrals.code_generation_max_attempts' => 3]);

        $this->app->bind(ReferralCodeGeneratorInterface::class, function () {
            return new class implements ReferralCodeGeneratorInterface {
                public function generate(): string
                {
                    return 'ALWAYS-SAME';
                }
            };
        });

        $this->program->links()->create([
            'user_id' => 1,
            'referral_code' => 'ALWAYS-SAME',
        ]);

        $this->expectException(ReferralCodeGenerationException::class);
        $this->expectExceptionMessage('Failed to generate a unique referral code after 3 attempts.');

        $this->program->links()->create(['user_id' => 2]);
    }

    public function testMaxAttemptsIsConfigurable(): void
    {
        config(['referrals.code_generation_max_attempts' => 5]);

        $callCount = 0;
        $this->app->bind(ReferralCodeGeneratorInterface::class, function () use (&$callCount) {
            return new class($callCount) implements ReferralCodeGeneratorInterface {
                public function __construct(private int &$callCount) {}

                public function generate(): string
                {
                    $this->callCount++;
                    return 'FIXED-CODE';
                }
            };
        });

        $this->program->links()->create([
            'user_id' => 1,
            'referral_code' => 'FIXED-CODE',
        ]);

        try {
            $this->program->links()->create(['user_id' => 2]);
        } catch (ReferralCodeGenerationException $e) {
            $this->assertEquals(5, $callCount, 'Generator should be called exactly max_attempts times');
            $this->assertStringContainsString('5 attempts', $e->getMessage());
            return;
        }

        $this->fail('Expected ReferralCodeGenerationException was not thrown.');
    }

    public function testGlobalUniquenessEnforcedAcrossPrograms(): void
    {
        $secondProgram = ReferralProgram::create([
            'name' => 'other',
            'title' => 'Other Program',
            'description' => 'Another program',
            'uri' => 'other',
        ]);

        $sharedCode = 'GLOBAL-01';

        $this->program->links()->create([
            'user_id' => 1,
            'referral_code' => $sharedCode,
        ]);

        $callCount = 0;
        $this->app->bind(ReferralCodeGeneratorInterface::class, function () use (&$callCount, $sharedCode) {
            return new class($callCount, $sharedCode) implements ReferralCodeGeneratorInterface {
                public function __construct(private int &$callCount, private string $sharedCode) {}

                public function generate(): string
                {
                    $this->callCount++;
                    return $this->callCount === 1 ? $this->sharedCode : 'PROGRAM2-OK';
                }
            };
        });

        $linkInOtherProgram = $secondProgram->links()->create(['user_id' => 2]);

        $this->assertEquals('PROGRAM2-OK', $linkInOtherProgram->referral_code);
        $this->assertEquals(2, $callCount, 'Collision detected across programs — second attempt produces unique code');
    }

    public function testNoCollisionWhenCodespaceIsSparse(): void
    {
        $links = [];
        for ($i = 1; $i <= 5; $i++) {
            $links[] = $this->program->links()->create(['user_id' => $i]);
        }

        $codes = array_map(fn ($link) => $link->referral_code, $links);

        $this->assertEquals(count($codes), count(array_unique($codes)), 'All generated codes should be unique');
    }
}
