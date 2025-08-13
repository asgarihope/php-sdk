<?php

namespace Radeir\Services;

use Radeir\DTOs\CardToIbanDTO;
use Radeir\Enums\ServiceEnum;
use Radeir\Exceptions\RadeException;
use Radeir\Helpers\NumberHelper;
use Radeir\Rules\CardValidationRule;
use Radeir\Services\TokenManager\TokenManagerInterface;
use Throwable;

class CardToIbanService extends AbstractServices
{
	public function __construct(
		TokenManagerInterface $tokenManager,
		array                 $config
	) {
		parent::__construct($tokenManager, $config);
	}

	public function cardToIban(string $cardNumber): CardToIbanDTO {
		try {
			$cardNumber = NumberHelper::convertToEnglishNumbers($cardNumber);
			$cardNumber = CardValidationRule::passes($cardNumber);

			$response = $this->makeRequest('POST', '/service/cardToIban', [
				'json' => [
					'card_number' => $cardNumber
				]
			]);

			$data = json_decode($response->getBody()->getContents(), true);
			if (isset($data['data']['result']['result'])) {
				return new CardToIbanDTO(
					$data['data']['RadeTraceID'],
					$data['data']['result']['result']['bankName'],
					$data['data']['result']['result']['bankEnum'],
					$data['data']['result']['result']['bankLogo'],
					$data['data']['result']['result']['IBAN'],
					$data['data']['result']['result']['card'],
					$data['data']['result']['result']['deposit'],
					$data['data']['result']['result']['depositOwners'],
				);
			}

			throw new RadeException('Invalid Response');
		} catch (Throwable $throwable) {
			throw $this->handleRequestException($throwable, ServiceEnum::CARD_TO_IBAN);
		}
	}
}
