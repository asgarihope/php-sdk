# Contribution Guidelines

Thank you for your interest in contributing to this project. This guide will help you understand the contribution process.

## Prerequisites

To contribute to this project, you need:

- PHP 8.1 or higher
- Composer
- PHPUnit for running tests
- Familiarity with PSR standards
- Knowledge of code consistency tools like `phpstan/phpstan` and `rector/rector`

## Contribution Process

1. First, fork the project.
2. Create a new branch for your changes:
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. Make your changes.
4. Write relevant tests.
5. Run tests to ensure your code is working correctly:
   ```bash
   composer test
   composer coverage
   composer stan
   composer refactor
   ```
6. Commit your changes:

   Use tags like FIX, ENHANCEMENT, FEATURE.
   ```bash
   git commit -m "[FIX] Appropriate description of changes"
   ```
7. Push the changes to your branch in your forked repository:
   ```bash
   git push origin feature/your-feature-name
   ```
8. Submit a Pull Request to the main branch of the project.

## Coding Standards

- Your code must follow the standards established in the project (referring to stan & refactor).
- Class names should be in PascalCase.
- Method and variable names should be in camelCase.
- All code should be written in English.

## Writing Tests

- Write appropriate tests for each new feature.
- Tests should include both Unit Tests and Feature Tests.
- Try to maintain a test coverage of at least 90%.

Example test:
```php
public function testCardToIbanSuccessful() {
    // Mock a successful API response
    $responseBody = json_encode([
        'data' => [
            'RadeTraceID' => '8bc0bad0-6c64-4d1d-8759-176b7bbc4b36"',
            'result'      => [
                'result' => [
                    'bankName'      => 'Test Bank',
                    'bankEnum'      => 'TEST_BANK',
                    'bankLogo'      => 'https://example.com/TEST_BANK.svg',
                    'IBAN'          => 'IR123456789012345678901234',
                    'card'          => '6104337812345678',
                    'deposit'       => '0123456789',
                    'depositOwners' => 'Omid Asgari'
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
    $result = $this->service->cardToIban('6104337812345678');

    // Assert the result is a CardToIbanDTO and has expected values
    $this->assertInstanceOf(CardToIbanDTO::class, $result);
    $this->assertEquals('8bc0bad0-6c64-4d1d-8759-176b7bbc4b36"', $result->trackID);
    $this->assertEquals('Test Bank', $result->bankName);
    $this->assertEquals('TEST_BANK', $result->bankEnum);
    $this->assertEquals('https://example.com/TEST_BANK.svg', $result->bankLogo);
    $this->assertEquals('IR123456789012345678901234', $result->iban);
    $this->assertEquals('6104337812345678', $result->cardNumber);
    $this->assertEquals('0123456789', $result->deposit);
    $this->assertEquals('Omid Asgari', $result->owners);
}
```

## Bug Reports

If you encounter a problem, please create a bug report in the Issues section including:

1. A detailed description of the issue
2. Steps to reproduce the problem
3. Expected output and actual output
4. PHP version and other relevant dependencies

## Feature Requests

To request a new feature, create a new Issue with the "enhancement" label and explain:

1. What problem does this feature solve?
2. How should it work?
3. Is this feature compatible with the current project architecture?

## Contact Us

If you need further guidance, you can contact us through:

- Email: support@rade.ir

Thank you for your contribution!
