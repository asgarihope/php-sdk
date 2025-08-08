<?php

namespace Radeir\Services;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Radeir\DTOs\DepositToIbanBankDTO;
use Radeir\DTOs\DepositToIbanDTO;
use Radeir\Exceptions\RadeClientException;
use Radeir\Exceptions\RadeException;
use Radeir\Exceptions\RadeServiceException;
use Radeir\Helpers\NumberHelper;

class DepositToIbanService extends AbstractServices
{

	public function depositToIban(string $depositNumber, string $bankCode): DepositToIbanDTO {
		try {
			$depositNumber = NumberHelper::convertToEnglishNumbers($depositNumber);

			$response = $this->makeRequest('POST', '/service/depositToIban', [
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
			throw new RadeException('Error in card to IBAN service: ' . $e->getMessage(), $e->getCode());
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
			throw new RadeException('Error in get bank list service: ' . $e->getMessage(), $e->getCode());
		}
	}

}
