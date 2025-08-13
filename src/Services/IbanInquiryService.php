<?php

namespace Radeir\Services;

use Radeir\DTOs\IbanInquiryDTO;
use Radeir\Enums\ServiceEnum;
use Radeir\Exceptions\RadeException;
use Radeir\Helpers\NumberHelper;
use Radeir\Rules\IbanValidationRule;
use Throwable;

class IbanInquiryService extends AbstractServices
{
	public function ibanInquiry(string $iban): IbanInquiryDTO {
		try {
			$iban = NumberHelper::convertToEnglishNumbers($iban);
			$iban = IbanValidationRule::passes($iban);

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
}
