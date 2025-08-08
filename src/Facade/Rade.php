<?php

namespace Radeir\Facade;

use Illuminate\Support\Facades\Facade;
use Radeir\Services\RadeServices;

class Rade extends Facade
{
	protected static function getFacadeAccessor()
	{
		return RadeServices::class;
	}
}
