<?php

namespace Radeir\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Radeir\DTOs\RadeTokenDTO;
use Radeir\Services\TokenManager\TokenManagerInterface;
use Throwable;

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
}
