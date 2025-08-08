<?php

namespace Radeir\DTOs;

class CardToDepositDTO
{
	public function __construct(
		public string $trackID,
		public string $bankName,
		public string $bankEnum,
		public string $bankLogo,
		public string $deposit,
		public string $destCard,
		public string $owners,
	) {
	}
}
