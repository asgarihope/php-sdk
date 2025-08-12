<?php

namespace Tests\Feature\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Radeir\DTOs\RadeTokenDTO;
use Radeir\Services\AbstractServices;
use Radeir\Services\TokenManager\AbstractTokenManagerService;
use Radeir\Services\TokenManager\TokenManagerInterface;

class AbstractServicesTest extends TestCase
{
	/**
	 * @var AbstractTokenManagerService
	 */
	private $tokenManager;

	/**
	 * @var array
	 */
	private $config;

	/**
	 * @var MockHandler
	 */
	private $mockHandler;

	/**
	 * Setup test environment
	 */
	protected function setUp(): void
	{
		parent::setUp();

		// Create a concrete implementation of the token manager for testing
		$this->tokenManager = $this->createTokenManager();

		$this->config = ['baseUrl' => 'https://api.example.com'];
		$this->mockHandler = new MockHandler();
	}

	/**
	 * Helper method to create a token DTO properly
	 */
	private function createTokenDTO(string $token, string $expireAt): RadeTokenDTO
	{
		$tokenDTO = new RadeTokenDTO();
		$tokenDTO->setAccessToken($token);
		$tokenDTO->setExpireAt($expireAt);
		return $tokenDTO;
	}

	/**
	 * Create a mock token manager that implements both the interface and adds the methods we need
	 */
	private function createTokenManager()
	{
		// Create a mock that looks like AbstractTokenManagerService
		// which has the methods we need to mock
		return new class($this) extends AbstractTokenManagerService {
			private $mockToken;
			private $shouldRefresh = false;
			private $testCase;

			public function __construct($testCase)
			{
				$this->testCase = $testCase;
				// Skip parent constructor
			}

			// We'll override ensureValidToken and refreshToken for testing
			public function ensureValidToken(): RadeTokenDTO
			{
				return $this->mockToken ?? $this->testCase->createTokenDTO('default_token', date('Y-m-d H:i:s', time() + 3600));
			}

			public function refreshToken(): void
			{
				$this->shouldRefresh = true;
				// Create a new token if needed
				if ($this->mockToken === null) {
					$this->mockToken = $this->testCase->createTokenDTO('refreshed_token', date('Y-m-d H:i:s', time() + 3600));
				}
			}

			// These are the interface methods we need to implement
			public function loadToken(): ?RadeTokenDTO
			{
				return $this->mockToken;
			}

			public function saveToken(string $access_token, string $expire_at): RadeTokenDTO
			{
				$this->mockToken = $this->testCase->createTokenDTO($access_token, $expire_at);
				return $this->mockToken;
			}

			// Test helper methods
			public function setMockToken(RadeTokenDTO $token)
			{
				$this->mockToken = $token;
			}

			public function wasRefreshCalled(): bool
			{
				return $this->shouldRefresh;
			}

			public function resetRefreshFlag(): void
			{
				$this->shouldRefresh = false;
			}
		};
	}

	/**
	 * Create a concrete implementation of the abstract class for testing
	 */
	private function createConcreteServices(MockHandler $mockHandler): AbstractServices
	{
		$handlerStack = HandlerStack::create($mockHandler);
		$client = new Client(['handler' => $handlerStack]);

		return new class($this->tokenManager, $this->config, $client) extends AbstractServices {
			public function __construct(
				TokenManagerInterface $tokenManager,
				array $config,
				Client $client
			) {
				parent::__construct($tokenManager, $config);
				// Override the client with our mock
				$this->httpClient = $client;
			}

			// Expose the protected method for testing
			public function testMakeRequest(string $method, string $endpoint, array $options = [])
			{
				return $this->makeRequest($method, $endpoint, $options);
			}
		};
	}

	/**
	 * Test successful request
	 */
	public function testSuccessfulRequest()
	{
		// Setup token
		$token = $this->createTokenDTO('test_token', date('Y-m-d H:i:s', time() + 3600));
		$this->tokenManager->setMockToken($token);

		// Setup mock response
		$this->mockHandler->append(new Response(200, [], '{"success":true}'));

		// Create service and make request
		$service = $this->createConcreteServices($this->mockHandler);
		$response = $service->testMakeRequest('GET', '/test-endpoint');

		// Assert response
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('{"success":true}', (string) $response->getBody());
		$this->assertFalse($this->tokenManager->wasRefreshCalled(), 'Token refresh should not be called for successful requests');
	}

	/**
	 * Test token refresh on 401 error
	 */
	public function testTokenRefreshOn401Error()
	{
		// Setup initial token
		$initialToken = $this->createTokenDTO('expired_token', date('Y-m-d H:i:s', time() + 3600));
		$this->tokenManager->setMockToken($initialToken);
		$this->tokenManager->resetRefreshFlag();

		// Setup mock responses:
		// 1. First request returns 401
		// 2. Retry with new token returns 200
		$this->mockHandler->append(
			new Response(401, [], '{"error":"Unauthorized"}'),
			new Response(200, [], '{"success":true}')
		);

		// Create service and make request
		$service = $this->createConcreteServices($this->mockHandler);
		$response = $service->testMakeRequest('GET', '/test-endpoint');

		// Assert final response is successful
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('{"success":true}', (string) $response->getBody());

		// Assert that token refresh was called
		$this->assertTrue($this->tokenManager->wasRefreshCalled(), 'Token refresh should be called for 401 errors');
	}

	/**
	 * Test that non-401 errors are rethrown
	 */
	public function testNon401ErrorsAreRethrown()
	{
		// Setup token
		$token = $this->createTokenDTO('test_token', date('Y-m-d H:i:s', time() + 3600));
		$this->tokenManager->setMockToken($token);
		$this->tokenManager->resetRefreshFlag();

		// Setup mock to return 404
		$this->mockHandler->append(
			new ClientException(
				'Not Found',
				new Request('GET', '/test-endpoint'),
				new Response(404, [], '{"error":"Not Found"}')
			)
		);

		// Create service
		$service = $this->createConcreteServices($this->mockHandler);

		// Expect exception to be thrown
		$this->expectException(ClientException::class);
		$service->testMakeRequest('GET', '/test-endpoint');

		// This assertion will only run if the exception is not thrown
		$this->assertFalse($this->tokenManager->wasRefreshCalled(), 'Token refresh should not be called for non-401 errors');
	}

	/**
	 * Test that after max retries, errors are rethrown
	 */
	public function testMaxRetriesExceeded()
	{
		// Setup token
		$token = $this->createTokenDTO('test_token', date('Y-m-d H:i:s', time() + 3600));
		$this->tokenManager->setMockToken($token);
		$this->tokenManager->resetRefreshFlag();

		// Setup mock to return 401 twice (original + 1 retry)
		$this->mockHandler->append(
			new ClientException(
				'Unauthorized',
				new Request('GET', '/test-endpoint'),
				new Response(401, [], '{"error":"Unauthorized"}')
			),
			new ClientException(
				'Unauthorized',
				new Request('GET', '/test-endpoint'),
				new Response(401, [], '{"error":"Unauthorized"}')
			)
		);

		// Create service
		$service = $this->createConcreteServices($this->mockHandler);

		// Expect exception to be thrown after retry
		$this->expectException(ClientException::class);
		$service->testMakeRequest('GET', '/test-endpoint');

		// This assertion will only run if the exception is not thrown
		$this->assertTrue($this->tokenManager->wasRefreshCalled(), 'Token refresh should be called for 401 errors');
	}
}
