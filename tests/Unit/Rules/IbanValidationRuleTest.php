<?php

namespace Tests\Unit\Rules;

use PHPUnit\Framework\TestCase;
use Radeir\Exceptions\InvalidInputException;
use Radeir\Rules\IbanValidationRule;

class IbanValidationRuleTest extends TestCase
{
    /**
     * Test that valid IBAN with IR prefix passes validation and returns the numeric part.
     */
    public function testPassesWithValidIbanWithIrPrefix()
    {
        $iban = 'IR123456789012345678901234';
        $result = IbanValidationRule::passes($iban);
        
        $this->assertEquals('123456789012345678901234', $result);
    }

    /**
     * Test that valid IBAN with lowercase ir prefix passes validation and returns the numeric part.
     */
    public function testPassesWithValidIbanWithLowercaseIrPrefix()
    {
        $iban = 'ir123456789012345678901234';
        $result = IbanValidationRule::passes($iban);
        
        $this->assertEquals('123456789012345678901234', $result);
    }

    /**
     * Test that valid IBAN with spaces passes validation.
     */
    public function testPassesWithValidIbanWithSpaces()
    {
        $iban = 'IR 1234 5678 9012 3456 7890 1234';
        $result = IbanValidationRule::passes($iban);
        
        $this->assertEquals('123456789012345678901234', $result);
    }

    /**
     * Test that valid IBAN without IR prefix passes validation.
     */
    public function testPassesWithValidIbanWithoutIrPrefix()
    {
        $iban = '123456789012345678901234';
        $result = IbanValidationRule::passes($iban);
        
        $this->assertEquals('123456789012345678901234', $result);
    }

    /**
     * Test that valid IBAN without IR prefix but with spaces passes validation.
     */
    public function testPassesWithValidIbanWithoutIrPrefixButWithSpaces()
    {
        $iban = '1234 5678 9012 3456 7890 1234';
        $result = IbanValidationRule::passes($iban);
        
        $this->assertEquals('123456789012345678901234', $result);
    }

    /**
     * Test that IBAN with invalid length throws exception.
     */
    public function testPassesWithInvalidLengthThrowsException()
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionCode(422);
        
        $iban = '12345678901234567890123'; // Only 23 digits instead of 24
        IbanValidationRule::passes($iban);
    }

    /**
     * Test that IBAN with IR prefix but invalid length throws exception.
     */
    public function testPassesWithIrPrefixAndInvalidLengthThrowsException()
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionCode(422);
        
        $iban = 'IR12345678901234567890123'; // Only 23 digits after IR instead of 24
        IbanValidationRule::passes($iban);
    }

    /**
     * Test that IBAN with non-numeric characters throws exception.
     */
    public function testPassesWithNonNumericCharactersThrowsException()
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionCode(422);
        
        $iban = '123456789012345678901ABC'; // Contains letters
        IbanValidationRule::passes($iban);
    }

    /**
     * Test that IBAN with IR prefix and non-numeric characters throws exception.
     */
    public function testPassesWithIrPrefixAndNonNumericCharactersThrowsException()
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionCode(422);
        
        $iban = 'IR123456789012345678901ABC'; // Contains letters after IR
        IbanValidationRule::passes($iban);
    }
}