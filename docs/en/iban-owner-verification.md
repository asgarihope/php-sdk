# IBAN Owner Verification Service

## Introduction
The IBAN Owner Verification service provided by Rade allows you to verify the correctness of information by sending an IBAN number, national ID, and date of birth of the user.

## Usage

### In Laravel

```php
use Illuminate\Support\Facades\Rade;

$result = Rade::ibanOwnerVerification('IR123456789012345678901234','0012345678','1370','03','05');
```

### Direct Usage

```php
<?php
use Radeir\Services\RadeServices;

// Configuration
$config = [
    'username' => 'your_username',
    'password' => 'your_password',
    'scopes' => ['scope1', 'scope2','ibanOwnerVerification'],
    'baseUrl' => 'https://api.rade.ir/api'
];

// Create service instance
$radeServices = new RadeServices($config);
$radeServices->ibanOwnerVerification('IR123456789012345678901234','0012345678','1370','11','22');
// Use the service
try {
   // Documentation for services provided is detailed in the #Services section below
} catch (\Radeir\Exceptions\RadeException $e) {
    // Error handling
    echo "Error: " . $e->getMessage();
}
```

## Input Parameters

You can enter the IBAN number you want to verify in the following formats:

- IR123456789012345678901234
- Ir123456789012345678901234
- iR123456789012345678901234
- ir123456789012345678901234
- IR۱۲۳۴۵۶۷۸۹۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴
- Ir۱۲۳۴۵۶۷۸۹۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴
- iR۱۲۳۴۵۶۷۸۹۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴
- ir۱۲۳۴۵۶۷۸۹۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴
- Without IR prefix

National ID (If it starts with 00, include it)

Year of birth as a 4-digit number in Persian calendar

Month of birth as a 2-character string, e.g., '03' for the third month

Day of birth as a 2-character string, e.g., '05' for the 5th day of the month

## Output

You should save the `trackID` as it is the only parameter stored on the Rade server and can be used for tracking purposes.

```json
{
	"trackID": "b6914995-f6ee-4ec0-8d65-d86a696227bb",
	"result": "yes" // 'yes' or 'no' or 'most_possible'
}
```

## Possible Errors

| Error Code | Message | Description |
|------------|---------|-------------|
| 422        | Invalid IBAN number | The format of the entered IBAN is incorrect. IBAN must be 24 digits without IR or 26 characters with IR. |
| 401        | Authentication error | National ID is not valid. |
| 401        | Authentication error | Invalid or expired token |
