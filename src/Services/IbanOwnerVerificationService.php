<?php

namespace Radeir\Services;

use Radeir\DTOs\IbanOwnerVerificationDTO;
use Radeir\Enums\ServiceEnum;
use Radeir\Exceptions\RadeException;
use Radeir\Rules\IbanValidationRule;
use Radeir\Rules\NationalityCodeValidationRule;
use Radeir\Services\TokenManager\TokenManagerInterface;
use Throwable;

class IbanOwnerVerificationService extends AbstractServices
{

	public function __construct(
		TokenManagerInterface $tokenManager,
		array                 $config
	) {
		parent::__construct($tokenManager, $config);
	}

	public function ibanOwnerVerification(
		string $iban,
		string $nationalityCode,
		string $birthDateYear,
		string $birthDateMonth,
		string $birthDateDay,
	): IbanOwnerVerificationDTO {
		try {
			$nationalityCode = NationalityCodeValidationRule::passes($nationalityCode);
			$iban            = IbanValidationRule::passes($iban);
			$response        = $this->makeRequest('POST', '/service/ibanOwnerVerification', [
				'json' => [
					'nid'      => $nationalityCode,
					'iban'     => $iban,
					'birthday' => [
						'year'  => $birthDateYear,
						'month' => $birthDateMonth,
						'day'   => $birthDateDay,
					]
				]
			]);
			$data            = json_decode($response->getBody()->getContents(), true);
			if (isset($data['data']['result'])) {
				return new IbanOwnerVerificationDTO(
					$data['data']['RadeTraceID'],
					$data['data']['result']
				);
			}

			throw new RadeException('Invalid Response');
		} catch (Throwable $throwable) {
			throw $this->handleRequestException($throwable, ServiceEnum::IBAN_OWNER_VERIFICATION);
		}
	}
}
