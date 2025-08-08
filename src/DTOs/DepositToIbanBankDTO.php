<?php

namespace Radeir\DTOs;

class DepositToIbanBankDTO
{
	public function __construct(
		public string $name,
		public string $code,
	) {
	}
}
