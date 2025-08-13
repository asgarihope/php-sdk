<?php

namespace Radeir\Rules;

use Radeir\Exceptions\InvalidInputException;

class IbanValidationRule
{
	public static function passes(string $iban): string {
		$normalizedIban = str_replace(' ', '', $iban);
		if (preg_match('/^[iI][rR]\d{24}$/i', $normalizedIban)) {
			return substr($normalizedIban, 2);
		}

		if (preg_match('/^\d{24}$/', $normalizedIban)) {
			return $normalizedIban;
		}

		throw new InvalidInputException('فرمت شماره شبا وارد شده صحیح نیست. شماره شبا باید 24 رقم بدون IR یا 26 کاراکتر با IR باشد.', 422);
	}
}
