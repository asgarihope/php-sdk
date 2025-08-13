<?php

namespace Radeir\Rules;

use Radeir\Exceptions\InvalidInputException;

class CardValidationRule
{
	public static function passes(string $cardNumber): string {
		// Check if it's in the format with dashes
		if (preg_match('/^\d{4}-\d{4}-\d{4}-\d{4}$/', $cardNumber)) {
			return str_replace('-', '', $cardNumber);
		}

		// Check if it's a plain 16-digit number
		if (preg_match('/^\d{16}$/', $cardNumber)) {
			return $cardNumber;
		}

		// If neither format matches, throw exception
		throw new InvalidInputException('فرمت شماره کارت وارد شده صحیح نیست. شماره‌کارت باید 16 رقم یا به فرمت 1111-2222-3333-4444 باشد.', 422);
	}
}
