<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use Radeir\Helpers\NumberHelper;

class NumberHelperTest extends TestCase
{
    public function testConvertToEnglishNumbers()
    {
        // تست اعداد فارسی
        $persianNumber = '۱۲۳۴۵';
        $this->assertEquals('12345', NumberHelper::convertToEnglishNumbers($persianNumber));
        
        // تست اعداد عربی
        $arabicNumber = '١٢٣٤٥';
        $this->assertEquals('12345', NumberHelper::convertToEnglishNumbers($arabicNumber));
        
        // تست ترکیبی از اعداد فارسی، عربی و انگلیسی
        $mixedNumber = '۱2٣4۵';
        $this->assertEquals('12345', NumberHelper::convertToEnglishNumbers($mixedNumber));
        
        // تست رشته خالی
        $emptyString = '';
        $this->assertEquals('', NumberHelper::convertToEnglishNumbers($emptyString));
        
        // تست رشته با متن و اعداد
        $textWithNumbers = 'مبلغ ۱۲۳۴۵ تومان';
        $this->assertEquals('مبلغ 12345 تومان', NumberHelper::convertToEnglishNumbers($textWithNumbers));
    }
}