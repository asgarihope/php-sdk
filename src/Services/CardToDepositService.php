<?php

namespace Radeir\Services;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Radeir\DTOs\CardToDepositDTO;
use Radeir\Exceptions\InvalidInputException;
use Radeir\Exceptions\RadeClientException;
use Radeir\Exceptions\RadeException;
use Radeir\Exceptions\RadeServiceException;
use Radeir\Helpers\NumberHelper;

class CardToDepositService extends AbstractServices
{

	public function cardToDeposit(string $cardNumber): CardToDepositDTO {
		try {
			$cardNumber = NumberHelper::convertToEnglishNumbers($cardNumber);
			$cardNumber = $this->validateCardNumber($cardNumber);

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
			throw new RadeException('Error in card to Deposit service: ' . $e->getMessage(), $e->getCode());
		}
	}

	private function validateCardNumber(string $cardNumber): string {
		// Check if it's in the format with dashes
		if (preg_match('/^\d{4}-\d{4}-\d{4}-\d{4}$/', $cardNumber)) {
			return str_replace('-', '', $cardNumber);
		}

		// Check if it's a plain 16-digit number
		if (preg_match('/^\d{16}$/', $cardNumber)) {
			return $cardNumber;
		}

		// If neither format matches, throw exception
		throw new InvalidInputException('فرمت شماره کارت وارد شده صحیح نیست. شماره‌کارت باید 16 رقم یا به فرمت 1111-2222-3333-4444 باشد.', 422);
	}
}
