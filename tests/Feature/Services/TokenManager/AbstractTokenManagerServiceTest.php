<?php

namespace Tests\Feature\Services\TokenManager;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Radeir\DTOs\RadeTokenDTO;
use Radeir\Exceptions\RadeAuthenticateException;
use Radeir\Exceptions\RadeClientException;
use Radeir\Exceptions\RadeException;
use Radeir\Exceptions\RadeServiceException;
use Radeir\Services\TokenManager\AbstractTokenManagerService;

/**
 * Custom RadeTokenDTO for testing to ensure proper initialization
 */
class TestRadeTokenDTO extends RadeTokenDTO
{
	public function __construct(string $token, string $expireAt)
	{
		$this->setAccessToken($token);
		$this->setExpireAt($expireAt);
	}
}

/**
 * Concrete implementation of AbstractTokenManagerService for testing
 */
class MockTokenManager extends AbstractTokenManagerService
{
	private ?RadeTokenDTO $savedToken = null;

	public function loadToken(): ?RadeTokenDTO
	{
		return $this->savedToken;
	}

	public function saveToken(string $access_token, string $expire_at): RadeTokenDTO
	{
		$this->savedToken = new TestRadeTokenDTO($access_token, $expire_at);
		return $this->savedToken;
	}
}

class AbstractTokenManagerServiceTest extends TestCase
{
	private $mockTokenManager;
	private $config;
	private $mockHandler;
	private $mockClient;

	protected function setUp(): void
	{
		parent::setUp();

		$this->config = [
			'username' => 'test_user',
			'password' => 'test_password',
			'scopes'   => ['read', 'write'],
			'baseUrl'  => 'https://api.example.com'
		];

		// Setup default mock handler and client
		$this->mockHandler = new MockHandler();
		$handlerStack = HandlerStack::create($this->mockHandler);
		$this->mockClient = new Client(['handler' => $handlerStack]);

		$this->mockTokenManager = new MockTokenManager($this->config, $this->mockClient);
	}

	public function testConstructorRequiresAllParameters()
	{
		// Test with missing username
		$invalidConfig = $this->config;
		unset($invalidConfig['username']);

		$this->expectException(RadeException::class);
		$this->expectExceptionMessage("Missing required configuration parameters: username");
		new MockTokenManager($invalidConfig);
	}

	public function testConstructorRequiresScopesAsArray()
	{
		// Test with scopes not as array
		$invalidConfig = $this->config;
		$invalidConfig['scopes'] = 'not_an_array';

		$this->expectException(RadeException::class);
		$this->expectExceptionMessage("Parameter 'scopes' must be an array");
		new MockTokenManager($invalidConfig);
	}

	public function testEnsureValidTokenWithValidExistingToken()
	{
		// Create a valid token (not expired)
		$expireAt = (new \DateTime())->add(new \DateInterval('PT1H'))->format('Y-m-d H:i:s');
		$token = new TestRadeTokenDTO('existing_token', $expireAt);

		// Set up the mock token manager to return our valid token
		$mockTokenManager = $this->getMockBuilder(MockTokenManager::class)
			->setConstructorArgs([$this->config, $this->mockClient])
			->onlyMethods(['loadToken'])
			->getMock();

		$mockTokenManager->expects($this->once())
			->method('loadToken')
			->willReturn($token);

		$resultToken = $mockTokenManager->ensureValidToken();

		$this->assertSame($token, $resultToken);
		$this->assertTrue($resultToken->valid());
		$this->assertEquals('existing_token', $resultToken->getAccessToken());
	}

	public function testEnsureValidTokenWithExpiredToken()
	{
		// Create an expired token (1 hour in the past)
		$expiredAt = (new \DateTime())->sub(new \DateInterval('PT1H'))->format('Y-m-d H:i:s');
		$expiredToken = new TestRadeTokenDTO('expired_token', $expiredAt);

		// Verify the token is actually invalid
		$this->assertFalse($expiredToken->valid(), 'Token should be invalid (expired)');

		// Setup future date for new token
		$futureDate = (new \DateTime())->add(new \DateInterval('PT1H'))->format('Y-m-d H:i:s');

		// Reset the mock handler and add our response
		$this->mockHandler->reset();
		$this->mockHandler->append(new Response(200, [], json_encode([
			'token' => 'new_token',
			'expires_at' => $futureDate
		])));

		// Setup the mock to return an expired token
		$mockTokenManager = $this->getMockBuilder(MockTokenManager::class)
			->setConstructorArgs([$this->config, $this->mockClient])
			->onlyMethods(['loadToken'])
			->getMock();

		$mockTokenManager->expects($this->once())
			->method('loadToken')
			->willReturn($expiredToken);

		// Execute the method
		$resultToken = $mockTokenManager->ensureValidToken();

		// Assert results
		$this->assertInstanceOf(RadeTokenDTO::class, $resultToken);
		$this->assertTrue($resultToken->valid(), 'The new token should be valid');
		$this->assertEquals('new_token', $resultToken->getAccessToken());
	}

	public function testGetTokenMethod()
	{
		// Setup future date for new token
		$futureDate = (new \DateTime())->add(new \DateInterval('PT1H'))->format('Y-m-d H:i:s');

		// Reset the mock handler and add our response
		$this->mockHandler->reset();
		$this->mockHandler->append(new Response(200, [], json_encode([
			'token' => 'direct_test_token',
			'expires_at' => $futureDate
		])));

		// Get a reflection of the protected method
		$reflection = new \ReflectionClass(MockTokenManager::class);
		$method = $reflection->getMethod('getToken');
		$method->setAccessible(true);

		// Call the protected method directly
		$token = $method->invokeArgs($this->mockTokenManager, [
			'test_user',
			'test_password',
			['read', 'write']
		]);

		// Assert results
		$this->assertInstanceOf(RadeTokenDTO::class, $token);
		$this->assertTrue($token->valid(), 'The token should be valid');
		$this->assertEquals('direct_test_token', $token->getAccessToken());
	}

	public function testSuccessfulTokenRetrieval()
	{
		// Set up future date for the token
		$futureDate = (new \DateTime())->add(new \DateInterval('PT1H'))->format('Y-m-d H:i:s');

		// Reset the mock handler and add our response
		$this->mockHandler->reset();
		$this->mockHandler->append(new Response(200, [], json_encode([
			'token' => 'new_token',
			'expires_at' => $futureDate
		])));

		// Setup the mock to return null for loadToken (no existing token)
		$mockTokenManager = $this->getMockBuilder(MockTokenManager::class)
			->setConstructorArgs([$this->config, $this->mockClient])
			->onlyMethods(['loadToken'])
			->getMock();

		$mockTokenManager->expects($this->once())
			->method('loadToken')
			->willReturn(null);

		// Execute the method that will use our mock client
		$token = $mockTokenManager->ensureValidToken();

		// Assert results
		$this->assertInstanceOf(RadeTokenDTO::class, $token);
		$this->assertTrue($token->valid(), 'The token should be valid');
		$this->assertEquals('new_token', $token->getAccessToken());
	}

	public function testAuthenticationException()
	{
		// Reset the mock handler and add our error response
		$this->mockHandler->reset();
		$this->mockHandler->append(
			new ClientException(
				'Unauthorized',
				new Request('POST', 'https://api.example.com/token'),
				new Response(401, [], json_encode(['message' => 'Invalid credentials']))
			)
		);

		// Setup the mock to return null for loadToken (no existing token)
		$mockTokenManager = $this->getMockBuilder(MockTokenManager::class)
			->setConstructorArgs([$this->config, $this->mockClient])
			->onlyMethods(['loadToken'])
			->getMock();

		$mockTokenManager->expects($this->once())
			->method('loadToken')
			->willReturn(null);

		// Expect the exception
		$this->expectException(RadeAuthenticateException::class);

		// Call the method that should throw the exception
		$mockTokenManager->ensureValidToken();
	}

	public function testClientException()
	{
		// Reset the mock handler and add our error response
		$this->mockHandler->reset();
		$this->mockHandler->append(
			new ClientException(
				'Bad Request',
				new Request('POST', 'https://api.example.com/token'),
				new Response(400, [], json_encode(['message' => 'Invalid request']))
			)
		);

		// Setup the mock to return null for loadToken (no existing token)
		$mockTokenManager = $this->getMockBuilder(MockTokenManager::class)
			->setConstructorArgs([$this->config, $this->mockClient])
			->onlyMethods(['loadToken'])
			->getMock();

		$mockTokenManager->expects($this->once())
			->method('loadToken')
			->willReturn(null);

		// Expect the exception
		$this->expectException(RadeClientException::class);

		// Call the method that should throw the exception
		$mockTokenManager->ensureValidToken();
	}

	public function testServerException()
	{
		// Reset the mock handler and add our error response
		$this->mockHandler->reset();
		$this->mockHandler->append(
			new ServerException(
				'Server Error',
				new Request('POST', 'https://api.example.com/token'),
				new Response(500, [], json_encode(['message' => 'Server error']))
			)
		);

		// Setup the mock to return null for loadToken (no existing token)
		$mockTokenManager = $this->getMockBuilder(MockTokenManager::class)
			->setConstructorArgs([$this->config, $this->mockClient])
			->onlyMethods(['loadToken'])
			->getMock();

		$mockTokenManager->expects($this->once())
			->method('loadToken')
			->willReturn(null);

		// Expect the exception
		$this->expectException(RadeServiceException::class);

		// Call the method that should throw the exception
		$mockTokenManager->ensureValidToken();
	}

	public function testInvalidResponseFormat()
	{
		// Reset the mock handler and add our response with invalid format
		$this->mockHandler->reset();
		$this->mockHandler->append(new Response(200, [], json_encode([
			'invalid_key' => 'value',
			'another_invalid_key' => 'value'
		])));

		// Setup the mock to return null for loadToken (no existing token)
		$mockTokenManager = $this->getMockBuilder(MockTokenManager::class)
			->setConstructorArgs([$this->config, $this->mockClient])
			->onlyMethods(['loadToken'])
			->getMock();

		$mockTokenManager->expects($this->once())
			->method('loadToken')
			->willReturn(null);

		// Expect the exception
		$this->expectException(RadeException::class);
		$this->expectExceptionMessage('Error getting token: Invalid response format from token endpoint');

		// Call the method that should throw the exception
		$mockTokenManager->ensureValidToken();
	}

	public function testGenericExceptionInGetToken()
	{
		// Create a mock handler that will throw a generic exception
		$mockHandler = new MockHandler([
			new \Exception('Generic error')
		]);
		$handlerStack = HandlerStack::create($mockHandler);
		$exceptionClient = new Client(['handler' => $handlerStack]);

		// Create a token manager with our exception-throwing client
		$mockTokenManager = new MockTokenManager($this->config, $exceptionClient);

		// Mock the loadToken method to return null
		$mockTokenManager = $this->getMockBuilder(MockTokenManager::class)
			->setConstructorArgs([$this->config, $exceptionClient])
			->onlyMethods(['loadToken'])
			->getMock();

		$mockTokenManager->expects($this->once())
			->method('loadToken')
			->willReturn(null);

		// Expect the exception
		$this->expectException(RadeException::class);
		$this->expectExceptionMessage('Error getting token: Generic error');

		// Call ensureValidToken which will trigger getToken
		$mockTokenManager->ensureValidToken();
	}
}
