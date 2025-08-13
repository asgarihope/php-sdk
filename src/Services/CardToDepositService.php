<?php

namespace Radeir\Services;

use Radeir\DTOs\CardToDepositDTO;
use Radeir\Enums\ServiceEnum;
use Radeir\Exceptions\RadeException;
use Radeir\Helpers\NumberHelper;
use Radeir\Rules\CardValidationRule;
use Throwable;

class CardToDepositService extends AbstractServices
{
	public function cardToDeposit(string $cardNumber): CardToDepositDTO {
		try {
			$cardNumber = NumberHelper::convertToEnglishNumbers($cardNumber);
			$cardNumber = CardValidationRule::passes($cardNumber);

			$response = $this->makeRequest('POST', '/service/cardToDeposit', [
				'json' => [
					'card_number' => $cardNumber
				]
			]);

			$data = json_decode($response->getBody()->getContents(), true);
			if (isset($data['data']['result']['result'])) {
				return new CardToDepositDTO(
					$data['data']['RadeTraceID'],
					$data['data']['result']['result']['bankName'],
					$data['data']['result']['result']['bankEnum'],
					$data['data']['result']['result']['bankLogo'],
					$data['data']['result']['result']['deposit'],
					$data['data']['result']['result']['destCard'],
					$data['data']['result']['result']['depositOwners'],
				);
			}

			throw new RadeException('Invalid Response');
		} catch (Throwable $throwable) {
			throw $this->handleRequestException($throwable, ServiceEnum::CARD_TO_DEPOSIT);
		}
	}

}
