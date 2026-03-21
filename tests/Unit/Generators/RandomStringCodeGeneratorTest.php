<?php

namespace Pdazcom\Referrals\Tests\Unit\Generators;

use Pdazcom\Referrals\Contracts\ReferralCodeGeneratorInterface;
use Pdazcom\Referrals\Generators\RandomStringCodeGenerator;
use Pdazcom\Referrals\Tests\TestCase;

class RandomStringCodeGeneratorTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $generator = new RandomStringCodeGenerator();

        $this->assertInstanceOf(ReferralCodeGeneratorInterface::class, $generator);
    }

    public function testGeneratesStringOfDefaultLength(): void
    {
        $generator = new RandomStringCodeGenerator();

        $code = $generator->generate();

        $this->assertIsString($code);
        $this->assertEquals(8, strlen($code));
    }

    public function testGeneratesStringOfCustomLength(): void
    {
        $generator = new RandomStringCodeGenerator(length: 12);

        $code = $generator->generate();

        $this->assertEquals(12, strlen($code));
    }

    public function testGeneratesCodesUsingAllowedCharset(): void
    {
        $charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $generator = new RandomStringCodeGenerator(length: 100);

        $code = $generator->generate();

        $this->assertMatchesRegularExpression('/^[' . $charset . ']+$/', $code);
    }

    public function testGeneratesCodesWithCustomCharset(): void
    {
        $charset = 'ABC123';
        $generator = new RandomStringCodeGenerator(length: 20, charset: $charset);

        $code = $generator->generate();

        $this->assertEquals(20, strlen($code));
        $this->assertMatchesRegularExpression('/^[ABC123]+$/', $code);
    }

    public function testGeneratedCodesAreUnique(): void
    {
        $generator = new RandomStringCodeGenerator(length: 8);

        $codes = array_map(fn () => $generator->generate(), range(1, 50));

        $this->assertCount(50, array_unique($codes), 'Generated codes should be unique across 50 samples');
    }

    public function testDefaultCharsetExcludesAmbiguousCharacters(): void
    {
        $generator = new RandomStringCodeGenerator(length: 200);
        $code = $generator->generate();

        $this->assertStringNotContainsString('0', $code, 'Should not contain 0 (ambiguous with O)');
        $this->assertStringNotContainsString('1', $code, 'Should not contain 1 (ambiguous with I/l)');
        $this->assertStringNotContainsString('I', $code, 'Should not contain I (ambiguous with 1/l)');
        $this->assertStringNotContainsString('O', $code, 'Should not contain O (ambiguous with 0)');
    }

    public function testServiceContainerResolvesDefaultGenerator(): void
    {
        $generator = app(ReferralCodeGeneratorInterface::class);

        $this->assertInstanceOf(RandomStringCodeGenerator::class, $generator);
    }

    public function testServiceContainerRespectsCodeLengthConfig(): void
    {
        config(['referrals.code_length' => 12]);

        $this->app->forgetInstance(ReferralCodeGeneratorInterface::class);

        $generator = app(ReferralCodeGeneratorInterface::class);
        $code = $generator->generate();

        $this->assertEquals(12, strlen($code));
    }

    public function testCustomGeneratorClassCanBeConfigured(): void
    {
        config(['referrals.code_generator' => RandomStringCodeGenerator::class]);

        $this->app->forgetInstance(ReferralCodeGeneratorInterface::class);

        $generator = app(ReferralCodeGeneratorInterface::class);

        $this->assertInstanceOf(RandomStringCodeGenerator::class, $generator);
    }
}
