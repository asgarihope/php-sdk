<?php

namespace Radeir\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\GuzzleException;
use Radeir\DTOs\RadeTokenDTO;
use Radeir\Enums\ServiceEnum;
use Radeir\Exceptions\RadeClientException;
use Radeir\Exceptions\RadeServiceException;
use Radeir\Exceptions\RadeException;
use Radeir\Services\TokenManager\TokenManagerInterface;
use Throwable;
use Exception;

abstract class AbstractServices
{
	protected Client $httpClient;

	protected string $baseUrl;

	public function __construct(
		protected TokenManagerInterface $tokenManager,
		protected array                 $config
	) {
		$this->baseUrl    = $config['baseUrl'] ?? '';
		$this->httpClient = new Client([
			'verify'   => false,
			'base_uri' => $this->baseUrl
		]);
	}

	protected function getToken(): RadeTokenDTO {
		return $this->tokenManager->ensureValidToken();
	}

	protected function makeRequest(string $method, string $endpoint, array $options = []) {
		$maxRetries = 1;
		$attempts   = 0;

		while ($attempts <= $maxRetries) {
			try {
				$radeTokenDTO = $this->getToken();

				$options['headers'] = array_merge(
					[
						'Accept'       => 'application/json',
						'Content-Type' => 'application/json'
					],
					$options['headers'] ?? [],
					['Authorization' => 'Bearer ' . $radeTokenDTO->getAccessToken()]
				);

				return $this->httpClient->request(
					$method,
					$this->baseUrl . $endpoint . '?applicant=php_package_sdk',
					$options
				);
			} catch (Throwable $e) {
				if ($e instanceof ClientException) {

					$response   = $e->getResponse();
					$statusCode = $response->getStatusCode();
					if ($statusCode === 401 && $attempts < $maxRetries) {
						$this->tokenManager->refreshToken();
						$attempts++;
						continue;
					}
				}

				throw $e;
			}
		}

		return null;
	}

	protected function handleRequestException(Throwable $throwable, ServiceEnum $serviceEnum): Throwable {
		if ($throwable instanceof ClientException) {
			$response     = $throwable->getResponse();
			$errorBody    = json_decode($response->getBody()->getContents(), true);
			$errorMessage = $errorBody['message'] ?? 'Client error: ' . $response->getStatusCode();

			return new RadeClientException($errorMessage, $response->getStatusCode());
		}

		if ($throwable instanceof ServerException) {
			$response     = $throwable->getResponse();
			$errorBody    = json_decode($response->getBody()->getContents(), true);
			$errorMessage = $errorBody['message'] ?? 'Server error: ' . $response->getStatusCode();

			return new RadeServiceException($errorMessage, $response->getStatusCode());
		}

		if ($throwable instanceof GuzzleException || $throwable instanceof Exception) {
			return new RadeException('Error in ' . $serviceEnum->value . ': ' . $throwable->getMessage(), $throwable->getCode());
		}

		return $throwable;
	}
}
