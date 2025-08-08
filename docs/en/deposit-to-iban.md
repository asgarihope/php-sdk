# Deposit Account to IBAN Service

## Introduction
The Deposit Account to IBAN service provided by Rade allows you to retrieve information such as IBAN number, account holder name, and account status by submitting an account number (exactly in the format of the relevant account number with . or -).

## Usage

### In Laravel

To use this service, you must also send the bank code related to the account number that is selected by the user.

```php
use Illuminate\Support\Facades\Rade;

$bankList = Rade::depositToIbanBankList();
```

The output of this service is an array of banks. You need to send the `code` to the inquiry service as explained below.

```json
[
	{
		"code": "001",
		"name": "Bank Number One"
	},
	{
		"code": "002",
		"name": "Bank Number Two"
	},
	... // Other banks
]
```

```php
use Illuminate\Support\Facades\Rade;

$result = Rade::depositToIban('1234-1234-1234567-1','001');
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
    $result = $radeServices->depositToIban('1234-1234-1234567-1','001');
    // Process the result
} catch (\Radeir\Exceptions\RadeException $e) {
    // Error handling
    echo "Error: " . $e->getMessage();
}
```

## Output

You should save the `trackID` as it is the only parameter stored on the Rade server and can be used for tracking purposes.

```json
{
	"trackID": "b6914995-f6ee-4ec0-8d65-d86a696227bb",
	"bankName": "Saman",
	"bankEnum": "SAMAN",
	"bankLogo": "https://api.rade.ir/images/banks/SAMAN.svg",
	"iban": "IR123456789012345678901234",
	"deposit": "1234-1234-1234567-1", // Each bank has its own account number format
	"owners": "امید عسگری (حساب فعال است)"
}
```

## Possible Errors

Since the format of account numbers varies by bank, no validation is performed on the input parameters.

| Error Code | Message | Description |
|------------|---------|-------------|
| 401        | Authentication error | Invalid or expired token |
