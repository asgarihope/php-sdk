<div align="right">

# سرویس حساب به شبا

## معرفی
سرویس تبدیل حساب به شبا رده این امکان را میدهد که با ارسال شماره حساب (دقیقا با فرمت شماره حساب مربوطه که با . یا - ) وارد شده اطلاعاتی مثل شماره شبا و شماره حساب و نام صاحب حساب و همچنین وضعیت حساب را در خروجی ارائه میدهد

## نحوه استفاده

### در لاراول

برای استفاده از این سرویس باید کد بانک مربوط به شماره حساب را که توسط کاربر انتخاب میشود را هم باید ارسال کنید.

</div>
<div align="left">

```php
use Illuminate\Support\Facades\Rade;

$bankList = Rade::depositToIbanBankList();
```


</div>
<div align="right">
خروجی این سرویس آرایه ای از بانک‌ها است که باید code را به سرویس استعلام که در ادامه توضیح داده شده ارسال کنید.

</div>
<div align="left">

```json
[
	{
		"code": "001",
		"name": "بانک شماره یک"
	},
	{
		"code": "002",
		"name": "بانک شماره دو"
	},
	... // باقی بانک ها
]
```

```php
use Illuminate\Support\Facades\Rade;

$result = Rade::depositToIban('1234-1234-1234567-1','001');
```

</div>
<div align="right">

### استفاده مستقیم

</div>
<div align="left">

```php
<?php
use Radeir\Services\RadeServices;

// تنظیمات
$config = [
    'username' => 'your_username',
    'password' => 'your_password',
    'scopes' => ['scope1', 'scope2'],
    'baseUrl' => 'https://api.rade.ir/api'
];

// ایجاد نمونه از سرویس
$radeServices = new RadeServices($config);
$radeServices->depositToIban('6219-0000-0000-0000','001');
// استفاده از سرویس‌ها
try {
   // مستندات سرویس های ارایه شده به تفکیک خدمات در پایین بخش  #سرویس‌ها آمده است
} catch (\Radeir\Exceptions\RadeException $e) {
    // مدیریت خطا
    echo "Error: " . $e->getMessage();
}
```

</div>
<div align="right">


## خروجی

شما میتوانید `trackID` را ذخیره کنید، چراکه این پارامتر تنها پارامتر ذخیره شده در سرور رده است و قابلیت پیگیری تنها با `trackID` است.

</div>
<div align="left">

```json
{
	"trackID": "b6914995-f6ee-4ec0-8d65-d86a696227bb",
	"bankName": "سامان",
	"bankEnum": "SAMAN",
	"bankLogo": "https://api.rade.ir/images/banks/SAMAN.svg",
	"iban": "IR123456789012345678901234",
	"deposit": "1234-1234-1234567-1", // شماره حساب هر بانک فرمت مخصوص به خود را دارد
	"owners": "امید عسگری (حساب فعال است)"
}
```

</div>
<div align="right">

## خطاهای احتمالی

بنا به اینکه فرمت شماره حساب هر بانک متفاوت است، هیچ اعتبارسنجی ای در پارامتر های ورودی انجام نمیشود.


| کد خطا | پیام | توضیحات                                           |
|--------|------|---------------------------------------------------|
| 401    | خطای احراز هویت | توکن نامعتبر یا منقضی شده است                     |

</div>
