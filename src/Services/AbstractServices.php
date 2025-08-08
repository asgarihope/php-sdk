<?php

namespace Radeir\Services;

use GuzzleHttp\Client;
use Radeir\DTOs\RadeTokenDTO;
use Radeir\Services\TokenManager\TokenManagerInterface;

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
		$radeTokenDTO = $this->getToken();

		$options['headers'] = array_merge(
			[
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json'
			],
			$options['headers'] ?? [],
			['Authorization' => 'Bearer ' . $radeTokenDTO->getAccessToken()]
		);

		return $this->httpClient->request($method, $this->baseUrl . $endpoint . '?applicant=php_package_sdk', $options);
	}
}
