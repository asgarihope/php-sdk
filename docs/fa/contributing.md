# راهنمای مشارکت در پروژه

از اینکه می‌خواهید در توسعه این پروژه مشارکت کنید، سپاسگزاریم. این راهنما به شما کمک می‌کند تا با فرآیند مشارکت آشنا شوید.

## پیش‌نیازها

برای مشارکت در این پروژه به موارد زیر نیاز دارید:

- PHP 8.1 یا بالاتر
- Composer
- PHPUnit برای اجرای تست‌ها
- آشنایی با استانداردهای PSR
- آشنایی با ابزارهای مدیریت یک ریختی کد مثل `phpstan/phpstan` و `rector/rector`

## روند مشارکت

1. ابتدا پروژه را Fork کنید.
2. یک شاخه (branch) جدید برای تغییرات خود ایجاد کنید:
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. تغییرات خود را اعمال کنید.
4. تست‌های مربوطه را بنویسید.
5. تست‌ها را اجرا کنید تا از صحت کد خود اطمینان حاصل کنید:
   ```bash
   composer test
   composer coverage
   composer stan
   composer refactor
   ```
6. تغییرات خود را کامیت کنید:

از برچسب های FIX,ENHANCEMENT,FEATURE استفاده کنید.
   ```bash
   git commit -m "[FIX] توضیحات مناسب برای تغییرات"
   ```
7. تغییرات را به شاخه خود در فورک پروژه پوش کنید:
   ```bash
   git push origin feature/your-feature-name
   ```
8. یک درخواست ادغام (Pull Request) به شاخه اصلی پروژه ارسال کنید.
9. 

## استانداردهای کدنویسی

- کد شما باید از استانداردهای تعیین شده در پروژه پیروی کند (منظور stan & refactor است).
- نام کلاس‌ها باید به صورت PascalCase باشد.
- نام متدها و متغیرها باید به صورت camelCase باشد.
- تمامی کدها باید به زبان انگلیسی نوشته شوند.

## نوشتن تست

- برای هر قابلیت جدید، تست‌های مناسب بنویسید.
- تست‌ها باید هم شامل تست‌های واحد (Unit Tests) و هم تست‌های کارکردی (Feature Tests) باشند.
- تلاش کنید پوشش تست (Test Coverage) حداقل 90% باشد.

نمونه تست:
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

## گزارش باگ

اگر با مشکلی مواجه شدید، لطفاً یک گزارش باگ در بخش Issues ایجاد کنید و موارد زیر را در آن ذکر کنید:

1. توضیح دقیق مشکل
2. مراحل بازتولید مشکل
3. خروجی مورد انتظار و خروجی واقعی
4. نسخه PHP و سایر وابستگی‌های مرتبط

## درخواست قابلیت جدید

برای درخواست قابلیت جدید، یک Issue جدید با برچسب "enhancement" ایجاد کنید و در آن توضیح دهید که:

1. این قابلیت چه مشکلی را حل می‌کند؟
2. چگونه باید کار کند؟
3. آیا این قابلیت با معماری فعلی پروژه سازگار است؟

## ارتباط با ما

در صورت نیاز به راهنمایی بیشتر، می‌توانید از طریق زیر با ما در ارتباط باشید:

- ایمیل: support@rade.ir

با سپاس از مشارکت شما!
