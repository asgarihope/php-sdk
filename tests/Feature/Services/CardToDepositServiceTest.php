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
use Radeir\DTOs\CardToDepositDTO;
use Radeir\DTOs\RadeTokenDTO;
use Radeir\Exceptions\RadeClientException;
use Radeir\Exceptions\RadeException;
use Radeir\Exceptions\RadeServiceException;
use Radeir\Services\CardToDepositService;
use Radeir\Services\TokenManager\AbstractTokenManagerService;

class CardToDepositServiceTest extends TestCase
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

		// Create a test subclass of CardToDepositService that allows us to inject our mock handler
		$this->service = new class($this->tokenManager, $this->config, $handlerStack) extends CardToDepositService{
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

	public function testCardToDepositSuccessful() {
		// Mock a successful API response
		$responseBody = json_encode([
			'data' => [
				'RadeTraceID' => 'trace123',
				'result'      => [
					'result' => [
						'bankName'      => 'Test Bank',
						'bankEnum'      => 'TEST_BANK',
						'bankLogo'      => 'https://example.com/logo.png',
						'deposit'       => '0123456789',
						'destCard'      => '6104337812345678',
						'depositOwners' => 'John Doe'
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
		$result = $this->service->cardToDeposit('6104337812345678');

		// Assert the result is a CardToDepositDTO and has expected values
		$this->assertInstanceOf(CardToDepositDTO::class, $result);
		$this->assertEquals('trace123', $result->trackID);
		$this->assertEquals('Test Bank', $result->bankName);
		$this->assertEquals('TEST_BANK', $result->bankEnum);
		$this->assertEquals('https://example.com/logo.png', $result->bankLogo);
		$this->assertEquals('0123456789', $result->deposit);
		$this->assertEquals('6104337812345678', $result->destCard);
		$this->assertEquals('John Doe', $result->owners);
	}

	public function testCardToDepositWithDashFormatting() {
		// Mock a successful API response
		$responseBody = json_encode([
			'data' => [
				'RadeTraceID' => 'trace123',
				'result'      => [
					'result' => [
						'bankName'      => 'Test Bank',
						'bankEnum'      => 'TEST_BANK',
						'bankLogo'      => 'https://example.com/logo.png',
						'deposit'       => '0123456789',
						'destCard'      => '6104337812345678',
						'depositOwners' => 'John Doe'
					]
				]
			]
		]);

		$this->mockHandler->append(new Response(
			200,
			['Content-Type' => 'application/json'],
			$responseBody
		));

		// Call the service with dashed card number format
		$result = $this->service->cardToDeposit('6104-3378-1234-5678');

		// Assert the result is a CardToDepositDTO
		$this->assertInstanceOf(CardToDepositDTO::class, $result);
		$this->assertEquals('6104337812345678', $result->destCard);
	}

	public function testCardToDepositWithInvalidCardFormat() {
		// The CardToDepositService wraps InvalidInputException in RadeException
		$this->expectException(RadeException::class);
		$this->expectExceptionMessage('فرمت شماره کارت وارد شده صحیح نیست');

		// Call the service with invalid card number
		$this->service->cardToDeposit('61043378123456'); // Only 14 digits
	}

	public function testCardToDepositClientException() {
		// Mock a client exception response
		$this->mockHandler->append(
			new ClientException(
				'Client error',
				new Request('POST', '/service/cardToDeposit'),
				new Response(
					400,
					['Content-Type' => 'application/json'],
					json_encode(['message' => 'Invalid card number'])
				)
			)
		);

		// Expect a RadeClientException
		$this->expectException(RadeClientException::class);

		// Call the service
		$this->service->cardToDeposit('6104337812345678');
	}

	public function testCardToDepositServerException() {
		// Mock a server exception response
		$this->mockHandler->append(
			new ServerException(
				'Server error',
				new Request('POST', '/service/cardToDeposit'),
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
		$this->service->cardToDeposit('6104337812345678');
	}

	public function testCardToDepositInvalidResponse() {
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
		$this->service->cardToDeposit('6104337812345678');
	}

	public function testCardToDepositWithPersianNumbers() {
		// Mock a successful API response
		$responseBody = json_encode([
			'data' => [
				'RadeTraceID' => 'trace123',
				'result'      => [
					'result' => [
						'bankName'      => 'Test Bank',
						'bankEnum'      => 'TEST_BANK',
						'bankLogo'      => 'https://example.com/logo.png',
						'deposit'       => '0123456789',
						'destCard'      => '6104337812345678',
						'depositOwners' => 'John Doe'
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
		$result = $this->service->cardToDeposit('۶۱۰۴۳۳۷۸۱۲۳۴۵۶۷۸');

		// Assert the result is a CardToDepositDTO
		$this->assertInstanceOf(CardToDepositDTO::class, $result);
	}

	public function testCardToDepositWithIncorrectDashPlacement() {
		// The CardToDepositService should throw an exception for incorrectly formatted dash pattern
		$this->expectException(RadeException::class);
		$this->expectExceptionMessage('فرمت شماره کارت وارد شده صحیح نیست');

		// Call the service with incorrectly placed dashes
		$this->service->cardToDeposit('6104-33781-234-5678');
	}

	public function testCardToDepositWithNonNumericCharacters() {
		// The CardToDepositService should throw an exception for non-numeric characters
		$this->expectException(RadeException::class);
		$this->expectExceptionMessage('فرمت شماره کارت وارد شده صحیح نیست');

		// Call the service with non-numeric characters
		$this->service->cardToDeposit('6104a37812345678');
	}

	public function testCardToDepositWithEmptyString() {
		// The CardToDepositService should throw an exception for empty string
		$this->expectException(RadeException::class);
		$this->expectExceptionMessage('فرمت شماره کارت وارد شده صحیح نیست');

		// Call the service with an empty string
		$this->service->cardToDeposit('');
	}

	public function testCardToDepositWithMixedValidDigitCountButInvalidDashes() {
		// even if the total digit count is correct
		$this->expectException(RadeException::class);
		$this->expectExceptionMessage('فرمت شماره کارت وارد شده صحیح نیست');

		// Call the service with 16 digits but invalid dash pattern
		$this->service->cardToDeposit('61043-37812-345678');
	}

}
