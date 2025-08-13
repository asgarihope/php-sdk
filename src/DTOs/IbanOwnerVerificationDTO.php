<?php

namespace Radeir\DTOs;

class IbanOwnerVerificationDTO
{

	public function __construct(
		public string $trackID,
		public string $result,
	) {
	}
}
