<?php

namespace Radeir\Enums;

enum ServiceEnum: string
{

	case CARD_TO_IBAN = 'cardToIban';
	case CARD_TO_DEPOSIT = 'cardToDeposit';
	case DEPOSIT_TO_IBAN = 'depositToIban';
	case IBAN_INQUIRY = 'ibanInquiry';
}
