<?php

namespace Tests\Unit;

use App\Support\Validator;
use Tests\TestCase;

class ValidatorTest extends TestCase
{
    public function testRequiredRejectsBlankAndWhitespaceOnly(): void
    {
        $this->assertFalse(Validator::required(''));
        $this->assertFalse(Validator::required('   '));
        $this->assertTrue(Validator::required('a'));
    }

    public function testEmailAllowsBlankButRejectsMalformed(): void
    {
        $this->assertTrue(Validator::email(''), 'blank email must be allowed — it means "no email given", not "invalid"');
        $this->assertTrue(Validator::email('a@b.com'));
        $this->assertFalse(Validator::email('not-an-email'));
    }

    public function testPositiveNumberRejectsNegativeAndNonNumeric(): void
    {
        $this->assertTrue(Validator::positiveNumber('0'));
        $this->assertTrue(Validator::positiveNumber('10.5'));
        $this->assertFalse(Validator::positiveNumber('-1'));
        $this->assertFalse(Validator::positiveNumber('abc'));
    }
}
