# IBAN Inquiry Service

## Introduction
The IBAN Inquiry service provided by Rade allows you to retrieve information such as account holder name and additional details by submitting an IBAN number.

## Usage

### In Laravel

```php
use Illuminate\Support\Facades\Rade;

$result = Rade::ibanInquiry('IR123456789012345678901234');
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
    $result = $radeServices->ibanInquiry('IR123456789012345678901234');
    // Process the result
} catch (\Radeir\Exceptions\RadeException $e) {
    // Error handling
    echo "Error: " . $e->getMessage();
}
```

## Input Parameters

You can enter the IBAN number you want to query in the following formats:

- IR123456789012345678901234
- Ir123456789012345678901234
- iR123456789012345678901234
- ir123456789012345678901234
- IR۱۲۳۴۵۶۷۸۹۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴
- Ir۱۲۳۴۵۶۷۸۹۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴
- iR۱۲۳۴۵۶۷۸۹۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴
- ir۱۲۳۴۵۶۷۸۹۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴
- Without IR prefix

## Output

You should save the `trackID` as it is the only parameter stored on the Rade server and can be used for tracking purposes.

```json
{
	"trackID": "b6914995-f6ee-4ec0-8d65-d86a696227bb",
	"bankName": "Saman",
	"bankEnum": "SAMAN",
	"bankLogo": "https://api.rade.ir/images/banks/SAMAN.svg",
	"owners": "امید عسگری (حساب فعال است)",
	"depositComment": "",
	"depositDescription": ""
}
```

## Possible Errors

| Error Code | Message | Description |
|------------|---------|-------------|
| 422        | Invalid IBAN number | The format of the entered IBAN is incorrect. IBAN must be 24 digits without IR or 26 characters with IR. |
| 401        | Authentication error | Invalid or expired token |
