<?php

namespace Tests\Unit\Rules;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Radeir\Exceptions\InvalidInputException;
use Radeir\Rules\NationalityCodeValidationRule;

class NationalityCodeValidationRuleTest extends TestCase
{
	/**
	 * Test valid nationality codes.
	 */
	#[DataProvider('validNationalityCodesProvider')]
	public function testValidNationalityCodes(string $nationalityCode): void
	{
		$result = NationalityCodeValidationRule::passes($nationalityCode);
		$this->assertEquals($nationalityCode, $result);
	}

	/**
	 * Test invalid nationality codes.
	 */
	#[DataProvider('invalidNationalityCodesProvider')]
	public function testInvalidNationalityCodes(string $nationalityCode): void
	{
		$this->expectException(InvalidInputException::class);
		$this->expectExceptionMessage('کد ملی وارد شده معتبر نیست.');
		$this->expectExceptionCode(422);

		NationalityCodeValidationRule::passes($nationalityCode);
	}

	/**
	 * Test nationality codes with incorrect format.
	 */
	#[DataProvider('incorrectFormatNationalityCodesProvider')]
	public function testIncorrectFormatNationalityCodes(string $nationalityCode): void
	{
		$this->expectException(InvalidInputException::class);
		$this->expectExceptionMessage('کد ملی وارد شده معتبر نیست.');
		$this->expectExceptionCode(422);

		NationalityCodeValidationRule::passes($nationalityCode);
	}

	/**
	 * Provides valid nationality codes for testing.
	 *
	 * @return array
	 */
	public static function validNationalityCodesProvider(): array
	{
		return [
			['0082633053'], // Valid Iranian nationality code
			['0082633045'], // Valid Iranian nationality code
			['0036448354'], // Valid Iranian nationality code
		];
	}

	/**
	 * Provides invalid nationality codes for testing.
	 *
	 * @return array
	 */
	public static function invalidNationalityCodesProvider(): array
	{
		return [
			['0082633054'], // Invalid check digit
			['0082633044'], // Invalid check digit
			['0036448355'], // Invalid check digit
			['0012345678'], // Invalid check digit
			['0067768564'], // Invalid check digit
		];
	}

	/**
	 * Provides nationality codes with incorrect format for testing.
	 *
	 * @return array
	 */
	public static function incorrectFormatNationalityCodesProvider(): array
	{
		return [
			['123456789'], // Too short
			['12345678901'], // Too long
			['abcdefghij'], // Non-numeric
			['123456789a'], // Contains non-numeric characters
			['00123456789'], // Too long
			['0012345'], // Too short
			[''], // Empty string
		];
	}
}
