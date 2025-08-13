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
use Radeir\DTOs\DepositToIbanBankDTO;
use Radeir\DTOs\DepositToIbanDTO;
use Radeir\DTOs\RadeTokenDTO;
use Radeir\Exceptions\RadeClientException;
use Radeir\Exceptions\RadeException;
use Radeir\Exceptions\RadeServiceException;
use Radeir\Services\DepositToIbanService;
use Radeir\Services\TokenManager\AbstractTokenManagerService;

class DepositToIbanServiceTest extends TestCase
{
    private $tokenManager;
    private $config;
    private $mockHandler;
    private $service;

    protected function setUp(): void
    {
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
        $handlerStack = HandlerStack::create($this->mockHandler);

        // Create a test subclass of DepositToIbanService that allows us to inject our mock handler
        $this->service = new class($this->tokenManager, $this->config, $handlerStack) extends DepositToIbanService {
            private $handlerStack;

            public function __construct($tokenManager, $config, $handlerStack)
            {
                $this->handlerStack = $handlerStack;
                parent::__construct($tokenManager, $config);

                // Override the httpClient after parent constructor
                $this->httpClient = new Client([
                    'verify' => false,
                    'base_uri' => $this->baseUrl,
                    'handler' => $this->handlerStack
                ]);
            }
        };
    }

    public function testDepositToIbanSuccessful()
    {
        // Mock a successful API response
        $responseBody = json_encode([
            'data' => [
                'RadeTraceID' => 'trace123',
                'result' => [
                    'result' => [
                        'bankName' => 'Test Bank',
                        'bankEnum' => 'TEST_BANK',
                        'bankLogo' => 'https://example.com/logo.png',
                        'IBAN' => 'IR123456789012345678901234',
                        'deposit' => '0123456789',
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
        $result = $this->service->depositToIban('0123456789', '056');

        // Assert the result is a DepositToIbanDTO and has expected values
        $this->assertInstanceOf(DepositToIbanDTO::class, $result);
        $this->assertEquals('trace123', $result->trackID);
        $this->assertEquals('Test Bank', $result->bankName);
        $this->assertEquals('TEST_BANK', $result->bankEnum);
        $this->assertEquals('https://example.com/logo.png', $result->bankLogo);
        $this->assertEquals('IR123456789012345678901234', $result->iban);
        $this->assertEquals('0123456789', $result->deposit);
        $this->assertEquals('John Doe', $result->owners);
    }

    public function testDepositToIbanWithPersianNumbers()
    {
        // Mock a successful API response
        $responseBody = json_encode([
            'data' => [
                'RadeTraceID' => 'trace123',
                'result' => [
                    'result' => [
                        'bankName' => 'Test Bank',
                        'bankEnum' => 'TEST_BANK',
                        'bankLogo' => 'https://example.com/logo.png',
                        'IBAN' => 'IR123456789012345678901234',
                        'deposit' => '0123456789',
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

        // Call the service with Persian numbers
        $result = $this->service->depositToIban('۰۱۲۳۴۵۶۷۸۹', 'MELLAT');

        // Assert the result is a DepositToIbanDTO
        $this->assertInstanceOf(DepositToIbanDTO::class, $result);
        $this->assertEquals('0123456789', $result->deposit);
    }

    public function testDepositToIbanClientException()
    {
        // Mock a client exception response
        $this->mockHandler->append(
            new ClientException(
                'Client error',
                new Request('POST', '/service/depositToIban'),
                new Response(
                    400,
                    ['Content-Type' => 'application/json'],
                    json_encode(['message' => 'Invalid deposit number'])
                )
            )
        );

        // Expect a RadeClientException
        $this->expectException(RadeClientException::class);

        // Call the service
        $this->service->depositToIban('0123456789', 'MELLAT');
    }

    public function testDepositToIbanServerException()
    {
        // Mock a server exception response
        $this->mockHandler->append(
            new ServerException(
                'Server error',
                new Request('POST', '/service/depositToIban'),
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
        $this->service->depositToIban('0123456789', 'MELLAT');
    }

    public function testDepositToIbanInvalidResponse()
    {
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
        $this->service->depositToIban('0123456789', 'MELLAT');
    }

    public function testGetBankListSuccessful()
    {
        // Mock a successful API response for bank list
        $responseBody = json_encode([
            'data' => [
                [
                    'name' => 'Mellat Bank',
                    'code' => 'MELLAT'
                ],
                [
                    'name' => 'Melli Bank',
                    'code' => 'MELLI'
                ],
                [
                    'name' => 'Saderat Bank',
                    'code' => 'SADERAT'
                ]
            ]
        ]);

        $this->mockHandler->append(new Response(
            200,
            ['Content-Type' => 'application/json'],
            $responseBody
        ));

        // Call the service
        $result = $this->service->getBankList();

        // Assert the result is an array of DepositToIbanBankDTO objects
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertInstanceOf(DepositToIbanBankDTO::class, $result[0]);
        $this->assertEquals('Mellat Bank', $result[0]->name);
        $this->assertEquals('MELLAT', $result[0]->code);
        $this->assertEquals('Melli Bank', $result[1]->name);
        $this->assertEquals('MELLI', $result[1]->code);
        $this->assertEquals('Saderat Bank', $result[2]->name);
        $this->assertEquals('SADERAT', $result[2]->code);
    }

    public function testGetBankListClientException()
    {
        // Mock a client exception response
        $this->mockHandler->append(
            new ClientException(
                'Client error',
                new Request('GET', '/service/banks/depositToIban'),
                new Response(
                    400,
                    ['Content-Type' => 'application/json'],
                    json_encode(['message' => 'Client error'])
                )
            )
        );

        // Expect a RadeClientException
        $this->expectException(RadeClientException::class);

        // Call the service
        $this->service->getBankList();
    }

    public function testGetBankListServerException()
    {
        // Mock a server exception response
        $this->mockHandler->append(
            new ServerException(
                'Server error',
                new Request('GET', '/service/banks/depositToIban'),
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
        $this->service->getBankList();
    }

    public function testGetBankListWithException()
    {
        // Mock a general exception
        $this->mockHandler->append(
            function (Request $request, array $options) {
                throw new \Exception('General error');
            }
        );

        // Expect a RadeException
        $this->expectException(RadeException::class);
        $this->expectExceptionMessage('Error in depositToIbanBankList: General error');

        // Call the service
        $this->service->getBankList();
    }
}
