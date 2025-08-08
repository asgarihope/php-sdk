<?php

namespace Radeir\Services;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Radeir\DTOs\IbanInquiryDTO;
use Radeir\Exceptions\InvalidInputException;
use Radeir\Exceptions\RadeClientException;
use Radeir\Exceptions\RadeException;
use Radeir\Exceptions\RadeServiceException;
use Radeir\Helpers\NumberHelper;
use Radeir\Services\TokenManager\TokenManagerInterface;

class IbanInquiryService extends AbstractServices
{

	public function __construct(
		TokenManagerInterface $tokenManager,
		array                 $config
	) {
		parent::__construct($tokenManager, $config);
	}

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

		} catch (ClientException $e) {
			$response     = $e->getResponse();
			$errorBody    = json_decode($response->getBody()->getContents(), true);
			$errorMessage = $errorBody['message'] ?? 'Client error: ' . $response->getStatusCode();

			throw new RadeClientException($errorMessage, $response->getStatusCode());

		} catch (ServerException $e) {
			$response     = $e->getResponse();
			$errorBody    = json_decode($response->getBody()->getContents(), true);
			$errorMessage = $errorBody['message'] ?? 'Server error: ' . $response->getStatusCode();

			throw new RadeServiceException($errorMessage, $response->getStatusCode());

		} catch (GuzzleException|Exception $e) {
			throw new RadeException('Error in IBAN inquiry service: ' . $e->getMessage(), $e->getCode());
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
