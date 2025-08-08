<?php

namespace Radeir\DTOs;

class IbanInquiryDTO
{
	public function __construct(
		public string  $trackID,
		public string  $bankName,
		public string  $bankEnum,
		public string  $bankLogo,
		public string  $owners,
		public ?string $depositComment,
		public ?string $depositDescription,
	) {
	}
}
