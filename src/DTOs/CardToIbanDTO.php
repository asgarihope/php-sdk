<?php

namespace Radeir\DTOs;

class CardToIbanDTO
{

	public function __construct(
		public string $trackID,
		public string $bankName,
		public string $bankEnum,
		public string $bankLogo,
		public string $iban,
		public string $cardNumber,
		public string $deposit,
		public string $owners
	) {
	}
}
