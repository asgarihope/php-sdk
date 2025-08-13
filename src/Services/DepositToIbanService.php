<?php

namespace Radeir\Services;

use Radeir\DTOs\DepositToIbanBankDTO;
use Radeir\DTOs\DepositToIbanDTO;
use Radeir\Enums\ServiceEnum;
use Radeir\Exceptions\RadeException;
use Radeir\Helpers\NumberHelper;
use Throwable;

class DepositToIbanService extends AbstractServices
{
	public function depositToIban(string $depositNumber, string $bankCode): DepositToIbanDTO {
		try {
			$depositNumber = NumberHelper::convertToEnglishNumbers($depositNumber);
			$response      = $this->makeRequest('POST', '/service/depositToIban', [
				'json' => [
					'deposit' => $depositNumber,
					'bank'    => $bankCode
				]
			]);

			$data = json_decode($response->getBody()->getContents(), true);
			if (isset($data['data']['result']['result'])) {
				return new DepositToIbanDTO(
					$data['data']['RadeTraceID'],
					$data['data']['result']['result']['bankName'],
					$data['data']['result']['result']['bankEnum'],
					$data['data']['result']['result']['bankLogo'],
					$data['data']['result']['result']['IBAN'],
					$data['data']['result']['result']['deposit'],
					$data['data']['result']['result']['depositOwners'],
				);
			}

			throw new RadeException('Invalid Response');
		} catch (Throwable $throwable) {
			throw $this->handleRequestException($throwable, ServiceEnum::DEPOSIT_TO_IBAN);
		}
	}

	public function getBankList(): array {
		try {
			$response = $this->makeRequest('GET', '/service/banks/depositToIban');

			$data = json_decode($response->getBody()->getContents(), true);
			$list = [];
			foreach ($data['data'] as $bank) {
				$list[] = new DepositToIbanBankDTO(
					$bank['name'],
					$bank['code']
				);
			}

			return $list;
		} catch (Throwable $throwable) {
			throw $this->handleRequestException($throwable, ServiceEnum::DEPOSIT_TO_IBAN_BANK_LIST);
		}
	}

}
