# Card to IBAN Service

## Introduction
The Card to IBAN service provided by Rade allows you to retrieve information such as IBAN number, account number, account holder name, and account status by submitting a card number.

## Usage

### In Laravel

```php
use Illuminate\Support\Facades\Rade;

$result = Rade::cardToIban('6219-0000-0000-0000');
```

### Direct Usage

```php
<?php
use Radeir\Services\RadeServices;

// Configuration
$config = [
    'username' => 'your_username',
    'password' => 'your_password',
    'scopes' => ['scope1', 'scope2'],
    'baseUrl' => 'https://api.rade.ir/api'
];

// Create service instance
$radeServices = new RadeServices($config);

// Use the service
try {
    $result = $radeServices->cardToIban('6219-0000-0000-0000');
    // Process the result
} catch (\Radeir\Exceptions\RadeException $e) {
    // Error handling
    echo "Error: " . $e->getMessage();
}
```

## Input Parameters

You can enter the card number you want to query and convert to IBAN in the following formats:
- 1234-5678-9012-3456
- 1234567890123456

## Output

You should save the `trackID` as it is the only parameter stored on the Rade server and can be used for tracking purposes.

```json
{
	"trackID": "b6914995-f6ee-4ec0-8d65-d86a696227bb",
	"bankName": "Saman",
	"bankEnum": "SAMAN",
	"bankLogo": "https://api.rade.ir/images/banks/SAMAN.svg",
	"iban": "IR123456789012345678901234",
	"cardNumber": "1234-5678-9012-3456",
	"deposit": "1234-1234-1234567-1", // Each bank has its own account number format
	"owners": "امید عسگری (حساب فعال است)"
}
```

## Possible Errors

| Error Code | Message | Description |
|------------|---------|-------------|
| 422        | Invalid card number | Card number must be 16 digits |
| 422        | Invalid IBAN number | IBAN must start with IR and contain 24 digits |
| 401        | Authentication error | Invalid or expired token |
