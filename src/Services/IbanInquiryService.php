<?php

namespace Radeir\Services;

use Radeir\DTOs\IbanInquiryDTO;
use Radeir\Enums\ServiceEnum;
use Radeir\Exceptions\InvalidInputException;
use Radeir\Exceptions\RadeException;
use Radeir\Helpers\NumberHelper;
use Throwable;

class IbanInquiryService extends AbstractServices
{
	public function ibanInquiry(string $iban): IbanInquiryDTO {
		try {
			$iban = NumberHelper::convertToEnglishNumbers($iban);
			$iban = $this->validateIban($iban);

			$response = $this->makeRequest('POST', '/service/ibanInquiry', [
				'json' => [
					'iban' => $iban
				]
			]);

			$data = json_decode($response->getBody()->getContents(), true);
			if (isset($data['data']['result']['result'])) {
				return new IbanInquiryDTO(
					$data['data']['RadeTraceID'],
					$data['data']['result']['result']['bankName'],
					$data['data']['result']['result']['bankEnum'],
					$data['data']['result']['result']['bankLogo'],
					$data['data']['result']['result']['depositOwners'],
					$data['data']['result']['result']['depositComment'],
					$data['data']['result']['result']['depositDescription'],
				);
			}

			throw new RadeException('Invalid Response');
		} catch (Throwable $throwable) {
			throw $this->handleRequestException($throwable, ServiceEnum::IBAN_INQUIRY);
		}
	}

	private function validateIban(string $iban): string {
		$normalizedIban = str_replace(' ', '', $iban);
		if (preg_match('/^[iI][rR]\d{24}$/i', $normalizedIban)) {
			return substr($normalizedIban, 2);
		}

		if (preg_match('/^\d{24}$/', $normalizedIban)) {
			return $normalizedIban;
		}

		throw new InvalidInputException('فرمت شماره شبا وارد شده صحیح نیست. شماره شبا باید 24 رقم بدون IR یا 26 کاراکتر با IR باشد.', 422);
	}
}
