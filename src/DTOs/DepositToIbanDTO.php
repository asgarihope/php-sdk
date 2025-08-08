<?php

namespace Radeir\DTOs;

class DepositToIbanDTO
{
	public function __construct(
		public string $trackID,
		public string $bankName,
		public string $bankEnum,
		public string $bankLogo,
		public string $iban,
		public string $deposit,
		public string $owners,
	) {
	}
}
