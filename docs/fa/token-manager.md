<div align="right">

# مدیریت توکن در سرویس‌های رده

## مقدمه
شما باید برای استفاده از تمامی سرویس های تحت API رده، از توکن احراز هویت استفاده کنید.
این قابلیت به شکل اتوماتیک در این پکیج نهادینه شده است و این توکن به صورت فایل ذخیره میشود.
معمولا در پروژه های حساس و بزرگ تمایل توسعه دهندگان به مدیریت در ذخیره سازی و حتی encrypt کردن و ذخیره توکن است پس در این پکیج شما در صورت تمایل میتوانید با پیاده سازی رابط یا interface   (`TokenManagerInterface`) مدیریت این امکان را خواهید داشت.

نکته مهم:
برای جلوگیری از چند ریختی و جلوگیری از متوقف شدن عملکرد پکیج حتمن به تولید dto برای مقدار return دقت فرمایید.


## ساختار مدیریت توکن
سیستم مدیریت توکن در این پکیج به صورت ماژولار طراحی شده و از دو بخش اصلی تشکیل شده است:

1. `saveToken` که مسئولیت ذخیره سازی بر اساس نیاز شما را به عهده دارد و حتما در خروجی نیز این توکن به صورت dto باید برگشد داده شود.
2. `loadToken` که مسيولیت بارگیری توکن از سیستم شمارا به عنده دارد و در خروجی این متد نیز dto مربوطه باید برگشد داده شود.

## نحوه استفاده

### پیاده‌سازی سفارشی TokenManager

اگر می‌خواهید توکن را در مکانی غیر از فایل (مثلاً دیتابیس) ذخیره کنید:

</div>
<div align="left">

```php
<?php

use Radeir\DTOs\RadeTokenDTO;
use Radeir\Services\RadeServices;
use Radeir\Services\TokenManager\TokenManagerInterface;

class DatabaseTokenManager implements TokenManagerInterface
{
    // پیاده‌سازی ذخیره توکن در دیتابیس
    public function saveToken(string $access_token, string $expire_at): RadeTokenDTO
    {
        // کد ذخیره توکن در دیتابیس
        
        $tokenDTO = new RadeTokenDTO();
        $tokenDTO->setAccessToken($access_token);
        $tokenDTO->setExpireAt($expire_at);
        return $tokenDTO;
    }
    
    public function loadToken(): ?RadeTokenDTO
    {
        // کد بازیابی توکن از دیتابیس
        $access_token= ....;
        $expire_at= ....;
        // تولید DTO برای یک ریختی کد
        if ($token && $expire_at){
          	$tokenDTO = new RadeTokenDTO();
        	$tokenDTO->setAccessToken($access_token);
        	$tokenDTO->setExpireAt($expire_at);
        } else {
        // اگر توکن وجود نداشت یا منقضی شده بود، null برگردانید تا عملیات گرفتن توکن از سرور انجام شود.
        	return null
        }
    }
}

// استفاده از مدیریت‌کننده سفارشی
$config = [...]; // تنظیمات
$tokenManager = new DatabaseTokenManager();
$radeServices = new RadeServices($config, $tokenManager);
```

</div>
<div align="right">

5. استفاده از TokenManager سفارشی در Laravel:

</div>

<div align="left">

```php
<?php

// در یک ServiceProvider
public function register()
{
    $this->app->bind(\Radeir\Services\TokenManager\TokenManagerInterface::class, function ($app) {
        return new DatabaseTokenManager();
    });
}
```

</div>
<div align="right">

## مدیریت خطاها

این پکیج خطاهای مختلف را به صورت ساختاریافته مدیریت می‌کند:

- **RadeException**: خطای پایه برای تمام خطاهای پکیج
- **RadeClientException**: خطاهای سمت کلاینت (مانند پارامترهای نامعتبر)
- **RadeServiceException**: خطاهای سمت سرور API

</div>
