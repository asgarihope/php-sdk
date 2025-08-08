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
use Radeir\DTOs\IbanInquiryDTO;
use Radeir\DTOs\RadeTokenDTO;
use Radeir\Exceptions\RadeClientException;
use Radeir\Exceptions\RadeException;
use Radeir\Exceptions\RadeServiceException;
use Radeir\Services\IbanInquiryService;
use Radeir\Services\TokenManager\AbstractTokenManagerService;

class IbanInquiryServiceTest extends TestCase
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

		// Create a test subclass of IbanInquiryService that allows us to inject our mock handler
		$this->service = new class($this->tokenManager, $this->config, $handlerStack) extends IbanInquiryService{
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

	public function testIbanInquirySuccessful() {
		// Mock a successful API response
		$responseBody = json_encode([
			'data' => [
				'RadeTraceID' => 'trace123',
				'result'      => [
					'result' => [
						'bankName'           => 'Test Bank',
						'bankEnum'           => 'TEST_BANK',
						'bankLogo'           => 'https://example.com/logo.png',
						'depositOwners'      => 'John Doe',
						'depositComment'     => 'Regular Account',
						'depositDescription' => 'Checking Account'
					]
				]
			]
		]);

		$this->mockHandler->append(new Response(
			200,
			['Content-Type' => 'application/json'],
			$responseBody
		));

		// Call the service
		$result = $this->service->ibanInquiry('IR123456789012345678901234');

		// Assert the result is an IbanInquiryDTO and has expected values
		$this->assertInstanceOf(IbanInquiryDTO::class, $result);
		$this->assertEquals('trace123', $result->trackID);
		$this->assertEquals('Test Bank', $result->bankName);
		$this->assertEquals('TEST_BANK', $result->bankEnum);
		$this->assertEquals('https://example.com/logo.png', $result->bankLogo);
		$this->assertEquals('John Doe', $result->owners);
		$this->assertEquals('Regular Account', $result->depositComment);
		$this->assertEquals('Checking Account', $result->depositDescription);
	}

	public function testIbanInquiryWithoutIRPrefix() {
		// Mock a successful API response
		$responseBody = json_encode([
			'data' => [
				'RadeTraceID' => 'trace123',
				'result'      => [
					'result' => [
						'bankName'           => 'Test Bank',
						'bankEnum'           => 'TEST_BANK',
						'bankLogo'           => 'https://example.com/logo.png',
						'depositOwners'      => 'John Doe',
						'depositComment'     => 'Regular Account',
						'depositDescription' => 'Checking Account'
					]
				]
			]
		]);

		$this->mockHandler->append(new Response(
			200,
			['Content-Type' => 'application/json'],
			$responseBody
		));

		// Call the service with IBAN without IR prefix
		$result = $this->service->ibanInquiry('123456789012345678901234');

		// Assert the result is an IbanInquiryDTO
		$this->assertInstanceOf(IbanInquiryDTO::class, $result);
	}

	public function testIbanInquiryWithSpaces() {
		// Mock a successful API response
		$responseBody = json_encode([
			'data' => [
				'RadeTraceID' => 'trace123',
				'result'      => [
					'result' => [
						'bankName'           => 'Test Bank',
						'bankEnum'           => 'TEST_BANK',
						'bankLogo'           => 'https://example.com/logo.png',
						'depositOwners'      => 'John Doe',
						'depositComment'     => 'Regular Account',
						'depositDescription' => 'Checking Account'
					]
				]
			]
		]);

		$this->mockHandler->append(new Response(
			200,
			['Content-Type' => 'application/json'],
			$responseBody
		));

		// Call the service with IBAN with spaces
		$result = $this->service->ibanInquiry('IR12 3456 7890 1234 5678 9012 34');

		// Assert the result is an IbanInquiryDTO
		$this->assertInstanceOf(IbanInquiryDTO::class, $result);
	}

	public function testIbanInquiryWithInvalidIbanFormat() {
		// The IbanInquiryService wraps InvalidInputException in RadeException
		$this->expectException(RadeException::class);
		$this->expectExceptionMessage('فرمت شماره شبا وارد شده صحیح نیست');

		// Call the service with invalid IBAN number (too short)
		$this->service->ibanInquiry('IR12345678901234567890123');
	}

	public function testIbanInquiryClientException() {
		// Mock a client exception response
		$this->mockHandler->append(
			new ClientException(
				'Client error',
				new Request('POST', '/service/ibanInquiry'),
				new Response(
					400,
					['Content-Type' => 'application/json'],
					json_encode(['message' => 'Invalid IBAN'])
				)
			)
		);

		// Expect a RadeClientException
		$this->expectException(RadeClientException::class);

		// Call the service
		$this->service->ibanInquiry('IR123456789012345678901234');
	}

	public function testIbanInquiryServerException() {
		// Mock a server exception response
		$this->mockHandler->append(
			new ServerException(
				'Server error',
				new Request('POST', '/service/ibanInquiry'),
				new Response(
					500,
					['Content-Type' => 'application/json'],
					json_encode(['message' => 'Internal server error'])
				)
			)
		);

		// Expect a RadeServiceException
		$this->expectException(RadeServiceException::class);

		// Call the service
		$this->service->ibanInquiry('IR123456789012345678901234');
	}

	public function testIbanInquiryInvalidResponse() {
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
		$this->expectExceptionMessage('Invalid Response');

		// Call the service
		$this->service->ibanInquiry('IR123456789012345678901234');
	}

	public function testIbanInquiryWithPersianNumbers() {
		// Mock a successful API response
		$responseBody = json_encode([
			'data' => [
				'RadeTraceID' => 'trace123',
				'result'      => [
					'result' => [
						'bankName'           => 'Test Bank',
						'bankEnum'           => 'TEST_BANK',
						'bankLogo'           => 'https://example.com/logo.png',
						'depositOwners'      => 'John Doe',
						'depositComment'     => 'Regular Account',
						'depositDescription' => 'Checking Account'
					]
				]
			]
		]);

		$this->mockHandler->append(new Response(
			200,
			['Content-Type' => 'application/json'],
			$responseBody
		));

		// Call the service with Persian numbers (mock functionality)
		// Assuming NumberHelper::convertToEnglishNumbers works as expected
		$result = $this->service->ibanInquiry('IR۱۲۳۴۵۶۷۸۹۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴');

		// Assert the result is an IbanInquiryDTO
		$this->assertInstanceOf(IbanInquiryDTO::class, $result);
	}

	public function testIbanInquiryWithLowercaseIR() {
		// Mock a successful API response
		$responseBody = json_encode([
			'data' => [
				'RadeTraceID' => 'trace123',
				'result'      => [
					'result' => [
						'bankName'           => 'Test Bank',
						'bankEnum'           => 'TEST_BANK',
						'bankLogo'           => 'https://example.com/logo.png',
						'depositOwners'      => 'John Doe',
						'depositComment'     => 'Regular Account',
						'depositDescription' => 'Checking Account'
					]
				]
			]
		]);

		$this->mockHandler->append(new Response(
			200,
			['Content-Type' => 'application/json'],
			$responseBody
		));

		// Call the service with lowercase "ir" prefix
		$result = $this->service->ibanInquiry('ir123456789012345678901234');

		// Assert the result is an IbanInquiryDTO
		$this->assertInstanceOf(IbanInquiryDTO::class, $result);
	}
}
