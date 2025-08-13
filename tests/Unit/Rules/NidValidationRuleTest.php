<?php

namespace Tests\Unit\Rules;

use PHPUnit\Framework\TestCase;
use Radeir\Exceptions\InvalidInputException;
use Radeir\Rules\NidValidationRule;

class NidValidationRuleTest extends TestCase
{
	/**
	 * Test that a valid 10-digit national ID passes validation.
	 */
	public function testPassesWithValidNationalId()
	{
		// Using a known valid national ID
		$validNationalId = '0067749828';
		$result = NidValidationRule::passes($validNationalId);
		$this->assertEquals($validNationalId, $result);
	}

	/**
	 * Test another valid national ID that meets the validation criteria.
	 */
	public function testPassesWithAnotherValidNationalId()
	{
		// Another valid national ID
		$nationalId = '0453089445';
		$result = NidValidationRule::passes($nationalId);
		$this->assertEquals($nationalId, $result);
	}

	/**
	 * Test that a national ID with incorrect check digit fails validation.
	 */
	public function testFailsWithInvalidCheckDigit()
	{
		$this->expectException(InvalidInputException::class);
		$this->expectExceptionCode(422);

		// Valid format but invalid check digit
		$nationalId = '0067749820'; // Changed last digit
		NidValidationRule::passes($nationalId);
	}

	/**
	 * Test that a national ID with non-numeric characters returns an empty string.
	 */
	public function testFailsWithNonNumericCharacters()
	{
		$nationalId = '12345678A0';
		$this->assertSame('', NidValidationRule::passes($nationalId));
	}

	/**
	 * Test that a national ID that's too short returns an empty string.
	 */
	public function testFailsWithTooShortNationalId()
	{
		$nationalId = '123456789';
		$this->assertSame('', NidValidationRule::passes($nationalId));
	}

	/**
	 * Test that a national ID that's too long returns an empty string.
	 */
	public function testFailsWithTooLongNationalId()
	{
		$nationalId = '12345678901';
		$this->assertSame('', NidValidationRule::passes($nationalId));
	}

	/**
	 * Test with a national ID that has valid format but fails the algorithm check.
	 */
	public function testFailsWithInvalidAlgorithmCheck()
	{
		$this->expectException(InvalidInputException::class);
		$this->expectExceptionCode(422);

		// 10 digits but invalid check digit
		$nationalId = '1234567890';
		NidValidationRule::passes($nationalId);
	}

	/**
	 * Test with a valid national ID that includes leading zeros.
	 */
	public function testPassesWithLeadingZeros()
	{
		// Valid national ID with leading zeros
		$validNationalId = '0010532129';
		$result = NidValidationRule::passes($validNationalId);
		$this->assertEquals($validNationalId, $result);
	}
}
