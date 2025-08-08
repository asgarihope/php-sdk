<div align="right">

# سرویس استعلام شبا

## معرفی
سرویساستعلام شبا رده این امکان را میدهد که با ارسال شماره شبا، نام صاحب حساب و توضیحاتی را در خروجی ارائه میدهد

## نحوه استفاده

### در لاراول
</div>
<div align="left">

```php
use Illuminate\Support\Facades\Rade;

$result = Rade::ibanInquiry('IR123456789012345678901234');
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
$radeServices->ibanInquiry('IR123456789012345678901234');
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

## پارامترهای ورودی

شماره شبا که میخواهید استعلام انجام دهید را میتوانید با قرمت های زیر وارد کنید.

- IR123456789012345678901234
- Ir123456789012345678901234
- iR123456789012345678901234
- ir123456789012345678901234
- IR۱۲۳۴۵۶۷۸۹۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴
- Ir۱۲۳۴۵۶۷۸۹۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴
- iR۱۲۳۴۵۶۷۸۹۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴
- ir۱۲۳۴۵۶۷۸۹۰۱۲۳۴۵۶۷۸۹۰۱۲۳۴
- بدون ir



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
	"owners": "امید عسگری (حساب فعال است)",
	"depositComment": "",
	"depositDescription": ""
}
```

</div>
<div align="right">

## خطاهای احتمالی

| کد خطا | پیام | توضیحات                                           |
|--------|------|---------------------------------------------------|
| 422    | شماره شبا نامعتبر است | فرمت شماره شبا وارد شده صحیح نیست. شماره شبا باید 24 رقم بدون IR یا 26 کاراکتر با IR باشد. |
| 401    | خطای احراز هویت | توکن نامعتبر یا منقضی شده است                     |

</div>
