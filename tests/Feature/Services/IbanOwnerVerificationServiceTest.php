<?php

namespace Tests\Feature\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Radeir\DTOs\IbanOwnerVerificationDTO;
use Radeir\DTOs\RadeTokenDTO;
use Radeir\Exceptions\RadeClientException;
use Radeir\Exceptions\RadeException;
use Radeir\Exceptions\RadeServiceException;
use Radeir\Services\IbanOwnerVerificationService;
use Radeir\Services\TokenManager\AbstractTokenManagerService;

class IbanOwnerVerificationServiceTest extends TestCase
{
	private $tokenManager;
	private $config;
	private $mockHandler;
	private $service;

	protected function setUp(): void {
		parent::setUp();

		// Create a mock token manager that extends AbstractTokenManagerService
		$this->tokenManager = $this->getMockBuilder(AbstractTokenManagerService::class)
			->disableOriginalConstructor()
			->getMock();

		// Create a properly initialized token DTO
		$tokenDTO = new RadeTokenDTO();
		$tokenDTO->setAccessToken('mock_access_token');
		$tokenDTO->setExpireAt('2099-12-31 23:59:59');

		// Configure the token manager mock to return our token DTO for ensureValidToken
		$this->tokenManager->method('ensureValidToken')
			->willReturn($tokenDTO);

		// Service configuration
		$this->config = [
			'baseUrl' => 'https://api.example.com'
		];

		// Create a mock handler for HTTP requests
		$this->mockHandler = new MockHandler();
		$handlerStack      = HandlerStack::create($this->mockHandler);

		// Create a test subclass of IbanOwnerVerificationService that allows us to inject our mock handler
		$this->service = new class($this->tokenManager, $this->config, $handlerStack) extends IbanOwnerVerificationService {
			private $handlerStack;

			public function __construct($tokenManager, $config, $handlerStack) {
				$this->handlerStack = $handlerStack;
				parent::__construct($tokenManager, $config);

				// Override the httpClient after parent constructor
				$this->httpClient = new Client([
					'verify'   => false,
					'base_uri' => $this->baseUrl,
					'handler'  => $this->handlerStack
				]);
			}
		};
	}

	public function testIbanOwnerVerificationSuccessful() {
		// Mock a successful API response - note that 'result' is cast to string by the DTO
		$responseBody = json_encode([
			'data' => [
				'RadeTraceID' => 'trace123',
				'result'      => 'yes' // Using string 'yes' to represent true
			]
		]);

		$this->mockHandler->append(new Response(
			200,
			['Content-Type' => 'application/json'],
			$responseBody
		));

		// Call the service with valid values
		$result = $this->service->ibanOwnerVerification(
			'IR860170000000111111111111', // Valid IBAN format
			'0067749828',                 // Valid national code format
			'1370',
			'05',
			'15'
		);

		// Assert the result is an IbanOwnerVerificationDTO and has expected values
		$this->assertInstanceOf(IbanOwnerVerificationDTO::class, $result);
		$this->assertEquals('trace123', $result->trackID);
		$this->assertEquals('yes', $result->result);
	}

	public function testIbanOwnerVerificationNegativeResult() {
		// Mock a successful API response with negative verification result
		$responseBody = json_encode([
			'data' => [
				'RadeTraceID' => 'trace123',
				'result'      => 'no' // Using 'no' string to represent false
			]
		]);

		$this->mockHandler->append(new Response(
			200,
			['Content-Type' => 'application/json'],
			$responseBody
		));

		// Call the service with valid values
		$result = $this->service->ibanOwnerVerification(
			'IR860170000000111111111111', // Valid IBAN format
			'0067749828',                 // Valid national code format
			'1370',
			'05',
			'15'
		);

		// Assert the result is an IbanOwnerVerificationDTO and verification is false
		$this->assertInstanceOf(IbanOwnerVerificationDTO::class, $result);
		$this->assertEquals('trace123', $result->trackID);
		$this->assertEquals('no', $result->result);
	}

	public function testIbanOwnerVerificationInvalidResponse() {
		// Mock a response with missing required data
		$responseBody = json_encode([
			'data' => [
				'RadeTraceID' => 'trace123',
				// Missing 'result' field
			]
		]);

		$this->mockHandler->append(new Response(
			200,
			['Content-Type' => 'application/json'],
			$responseBody
		));

		// Expect a RadeException for invalid response format
		$this->expectException(RadeException::class);

		// Call the service with valid values
		$this->service->ibanOwnerVerification(
			'IR860170000000111111111111', // Valid IBAN format
			'0067749828',                 // Valid national code format
			'1370',
			'05',
			'15'
		);
	}

	public function testIbanOwnerVerificationClientException() {
		// Mock a client exception response
		$this->mockHandler->append(
			new ClientException(
				'Client error',
				new Request('POST', '/service/ibanOwnerVerification'),
				new Response(
					400,
					['Content-Type' => 'application/json'],
					json_encode(['message' => 'Invalid data'])
				)
			)
		);

		// The service will wrap ClientException in RadeClientException or RadeException
		$this->expectException(RadeClientException::class);

		// Call the service with valid values
		$this->service->ibanOwnerVerification(
			'IR860170000000111111111111', // Valid IBAN format
			'0067749828',                 // Valid national code format
			'1370',
			'05',
			'15'
		);
	}

	public function testIbanOwnerVerificationServerException() {
		// Mock a server exception response
		$this->mockHandler->append(
			new ServerException(
				'Server error',
				new Request('POST', '/service/ibanOwnerVerification'),
				new Response(
					500,
					['Content-Type' => 'application/json'],
					json_encode(['message' => 'Internal server error'])
				)
			)
		);

		// The service will wrap ServerException in RadeServiceException or RadeException
		$this->expectException(RadeServiceException::class);

		// Call the service with valid values
		$this->service->ibanOwnerVerification(
			'IR860170000000111111111111', // Valid IBAN format
			'0067749828',                 // Valid national code format
			'1370',
			'05',
			'15'
		);
	}

	public function testIbanOwnerVerificationWithSpaces() {
		// Mock a successful API response
		$responseBody = json_encode([
			'data' => [
				'RadeTraceID' => 'trace123',
				'result'      => 'yes' // Using string 'yes' to represent true
			]
		]);

		$this->mockHandler->append(new Response(
			200,
			['Content-Type' => 'application/json'],
			$responseBody
		));

		// Call the service with IBAN with spaces
		$result = $this->service->ibanOwnerVerification(
			'IR86 0170 0000 0011 1111 1111 11', // Valid IBAN format with spaces
			'0067749828',                       // Valid national code format
			'1370',
			'05',
			'15'
		);

		// Assert the result is an IbanOwnerVerificationDTO
		$this->assertInstanceOf(IbanOwnerVerificationDTO::class, $result);
		$this->assertEquals('yes', $result->result);
	}

	public function testIbanOwnerVerificationWithoutIRPrefix() {
		// Mock a successful API response
		$responseBody = json_encode([
			'data' => [
				'RadeTraceID' => 'trace123',
				'result'      => 'yes' // Using string 'yes' to represent true
			]
		]);

		$this->mockHandler->append(new Response(
			200,
			['Content-Type' => 'application/json'],
			$responseBody
		));

		// Call the service with IBAN without IR prefix
		$result = $this->service->ibanOwnerVerification(
			'860170000000111111111111', // Valid IBAN format without IR prefix
			'0067749828',              // Valid national code format
			'1370',
			'05',
			'15'
		);

		// Assert the result is an IbanOwnerVerificationDTO
		$this->assertInstanceOf(IbanOwnerVerificationDTO::class, $result);
		$this->assertEquals('yes', $result->result);
	}
}
