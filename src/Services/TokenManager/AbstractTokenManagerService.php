<?php

namespace Radeir\Services\TokenManager;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Radeir\DTOs\RadeTokenDTO;
use Radeir\Exceptions\RadeAuthenticateException;
use Radeir\Exceptions\RadeClientException;
use Radeir\Exceptions\RadeException;
use Radeir\Exceptions\RadeServiceException;

abstract class AbstractTokenManagerService implements TokenManagerInterface
{
	protected ?RadeTokenDTO $token = null;

	private string $baseUrl;

	protected \GuzzleHttp\ClientInterface $httpClient;

	public function __construct(
		protected array $config,
		?ClientInterface $httpClient = null
	) {
		$requiredParams = ['username', 'password', 'scopes', 'baseUrl'];
		$missingParams  = array_diff($requiredParams, array_keys(array_filter($this->config)));

		if ($missingParams !== []) {
			throw new RadeException("Missing required configuration parameters: " . implode(', ', $missingParams));
		}

		if (!is_array($this->config['scopes'])) {
			throw new RadeException("Parameter 'scopes' must be an array");
		}

		$this->baseUrl = $this->config['baseUrl'];
		$this->httpClient = $httpClient ?? new Client(['verify' => true]);
	}

	protected function getToken(string $username, string $password, array $scopes): RadeTokenDTO {
		try {
			$response = $this->httpClient->post($this->baseUrl . '/token', [
				'headers' => [
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json'
				],
				'json'    => [
					'username' => $username,
					'password' => $password,
					'scopes'   => $scopes
				]
			]);
			$responseData = json_decode($response->getBody()->getContents(), true);
			if (isset($responseData['token'], $responseData['expires_at'])) {
				return $this->saveToken(
					$responseData['token'],
					$responseData['expires_at']
				);
			}

			throw new Exception('Invalid response format from token endpoint', 400);

		} catch (ClientException $e) {
			$response     = $e->getResponse();
			$errorBody    = json_decode($response->getBody()->getContents(), true);
			$errorMessage = $errorBody['message'] ?? 'Client error: ' . $response->getStatusCode();
			if ($e->getCode() === 401) {
				throw new RadeAuthenticateException($errorMessage, $response->getStatusCode());
			}

			throw new RadeClientException($errorMessage, $response->getStatusCode());

		} catch (ServerException $e) {
			$response     = $e->getResponse();
			$errorBody    = json_decode($response->getBody()->getContents(), true);
			$errorMessage = $errorBody['message'] ?? 'Server error: ' . $response->getStatusCode();

			throw new RadeServiceException($errorMessage, $response->getStatusCode());

		} catch (GuzzleException|Exception $e) {
			throw new RadeException('Error getting token: ' . $e->getMessage(), $e->getCode());
		}
	}

	public function ensureValidToken(): RadeTokenDTO {
		$this->token = $this->loadToken();
		if (!$this->token instanceof RadeTokenDTO || !$this->token->valid()) {
			$this->token = $this->getToken(
				$this->config['username'],
				$this->config['password'],
				$this->config['scopes']
			);
		}

		return $this->token;
	}

	abstract public function loadToken(): ?RadeTokenDTO;

	abstract public function saveToken(string $access_token, string $expire_at): RadeTokenDTO;
}
